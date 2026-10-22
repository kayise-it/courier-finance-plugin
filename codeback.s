/**
 * Unified sync pipeline:
 * 1) Waybills (IMPORTRANGE-fed) -> kit_waybills
 * 2) kit_waybills -> kit_customers (append-only)
 * 3) Backfill missing kit_waybills.customer_id from newly created customers
 *
 * Deploy: Google Sheets runs the code in Extensions → Apps Script, not this repo file.
 * After editing here, copy/paste this entire file into that project, Save, then run sync (or triggers).
 * Otherwise the sheet/DB will keep old serialized miscellaneous (e.g. legacy "client Invoice" label).
 */

/** `miscellaneous.others` key only — customer's purchase invoice ref (no duplicate misc_items row). */
var OTHERS_KEY_CLIENT_INVOICE_ = "client_invoice";

var CFG = {
  sourceSheetName: "Waybills",
  targetSheetName: "kit_waybills",
  customersSheetName: "kit_customers",
  deliveriesSheetName: "kit_deliveries",
  citiesSheetName: "kit_operating_cities",
  dataStartRow: 2,
  clearThroughRow: 0, // 0 = clear to current last row.
  writeErrorSheet: true,
  errorSheetName: "sync_errors",
  enableOnEdit: true,
  enableOnChange: false,
  // Short timeout intentionally: when a change/edit trigger storm fires (e.g. IMPORTRANGE
  // refreshing the source sheet repeatedly), we'd rather skip the overlapping run than
  // queue it for 30s and crash with "Sync lock timeout" once the previous sync overruns.
  lockTimeoutMs: 1000,
  countryIdDefault: 2,
  defaultDirectionId: 2,
  customerSeedMinId: 8600,
  /**
   * When true, product_invoice_number may use Waybills "cl inv" / mapped column (e.g. IMPORTRANGE AA).
   * When false (default), that column is ignored — only kit_waybills + allocator apply (see resolveProductInvoiceNumber_).
   */
  productInvoiceUseWaybillsSheet: false,
  /**
   * product_invoice_number resolution (see resolveProductInvoiceNumber_). If productInvoiceUseWaybillsSheet is true, clInv participates as noted; if false, Waybills invoice column is never read.
   * "preserve" — (optional clInv) then existing kit_waybills row, else allocate INV-…
   * "sheet_or_allocate" — with Waybills: clInv or allocate; without Waybills: allocate only (ignores stored kit invoice)
   * "always_allocate" — new INV-… every sync row
   */
  productInvoiceNumberMode: "always_allocate",
  /** WP user id for created_by / last_updated_by when row has no prior created_by (e.g. 1597). */
  waybillSyncUserId: ""
};

var SRC = {
  waybillNo: { required: true, aliases: ["waybill #", "waybill#", "waybill no", "waybill no.", "wb #", "wb no", "wb_no", "parcel_id", "parcel id", "parcel no", "parcel_no", "newwb", "new wb", "no", "no.", "number"] },
  itemDesc: { required: false, aliases: ["item description", "item desc", "items description"] },
  waybillDesc: { required: false, aliases: ["waybill description", "wb description"] },
  directionId: { required: false, aliases: ["direction_id", "direction id", "direction"] },
  cityId: { required: false, aliases: ["city_id", "city id", "destination_city_id"] },
  cityName: { required: false, aliases: ["city", "city_name", "destination_city", "destination city", "city_name_ignore"] },
  // Per client (Mel Ltd, 2026-05-19): source sheet column B is just labelled "Delivery" and
  // holds the truck/delivery ID (one delivery_id == one truck trip). Accept that header alongside
  // the more explicit "delivery_id" variants so the ID actually flows into kit_waybills.
  deliveryId: { required: false, aliases: ["delivery_id", "delivery id", "del_id", "delivery"] },
  customerId: { required: false, aliases: ["customer_id", "customer id", "cust_id"] },
  approval: { required: false, aliases: ["approval"] },
  approvalUserId: { required: false, aliases: ["approval_userid", "approval_user_id", "approval user id"] },
  customer: { required: false, aliases: ["customer", "cust name", "client"] },
  clInv: { required: false, aliases: ["cl inv #", "cl inv", "client invoice", "inv #", "inv no", "invoice #"] },
  custInvR: { required: false, aliases: ["customer inv( r)", "customer inv", "customer inv r", "customer inv (r)", "cust inv"] },
  sad500: { required: false, aliases: ["sad500", "sad 500"] },
  sadc: { required: false, aliases: ["sadc"] },
  length: { required: false, aliases: ["length", "item length", "len"] },
  width: { required: false, aliases: ["width", "item width"] },
  height: { required: false, aliases: ["height", "item height"] },
  tMass: { required: false, aliases: ["t mass", "t-mass", "total mass", "total_mass_kg", "mass kg"] },
  tVol: { required: false, aliases: ["t volume", "t-volume", "total volume", "total_volume", "volume"] },
  massCost: { required: false, aliases: ["mass cost", "mass_charge", "mass charge"] },
  volCost: { required: false, aliases: ["vol cost", "volume cost", "volume_charge", "vol charge"] },
  basis: { required: false, aliases: ["basis", "charge basis", "charge_basis"] },
  vat: { required: false, aliases: ["vat", "vat include", "vat_include", "vat included"] },
  tracking: { required: false, aliases: ["tracking number", "tracking_number", "tracking", "track no", "new wb"] },
  status: { required: false, aliases: ["status", "state"] },
  parcelText: { required: false, aliases: ["parcel", "parcel #", "parcel number", "waybill label"] },
  /** kit_waybills.created_at — Waybills column "Date Received" (also matches "Date Recieved"). */
  dateReceived: { required: false, aliases: ["date received", "date recieved", "date_received", "received date"] },
  /** Optional: customer's own invoice ref (same meaning as CL INV # / column AA) — not a rand charge. */
  clientInvoiceAa: { required: false, aliases: ["client invoice aa", "waybills aa", "column aa", "misc client invoice"] }
};

var TGT = {
  id: { required: true, aliases: ["id"] },
  parcel_id: { required: true, aliases: ["parcel_id", "parcel id", "parcel_no", "parcel no", "parcel #", "parcel number", "parcelid", "pkg id", "package id"] },
  waybill_no: { required: true, aliases: ["waybill_no", "waybill no", "waybill number"] },
  description: { required: true, aliases: ["description"] },
  direction_id: { required: false, aliases: ["direction_id", "direction id"] },
  city_id: { required: false, aliases: ["city_id", "city id"] },
  delivery_id: { required: false, aliases: ["delivery_id", "delivery id"] },
  cust_name_ignore: { required: false, aliases: ["cust_name_ignore", "cust name", "customer name ignore"] },
  customer_id: { required: false, aliases: ["customer_id", "customer id", "cust_id"] },
  approval: { required: false, aliases: ["approval"] },
  approval_userid: { required: false, aliases: ["approval_userid", "approval_user_id"] },
  product_invoice_number: { required: false, aliases: ["product_invoice_number", "product invoice number", "cl inv"] },
  product_invoice_amount: { required: false, aliases: ["product_invoice_amount", "product invoice amount"] },
  sad500_amount: { required: false, aliases: ["sad500_amount", "sad500 amount"] },
  sadc_amount: { required: false, aliases: ["sadc_amount", "sadc amount"] },
  item_length: { required: false, aliases: ["item_length", "item length", "length"] },
  item_width: { required: false, aliases: ["item_width", "item width", "width"] },
  item_height: { required: false, aliases: ["item_height", "item height", "height"] },
  total_mass_kg: { required: false, aliases: ["total_mass_kg", "total mass kg", "t mass"] },
  total_volume: { required: false, aliases: ["total_volume", "total volume", "t volume"] },
  mass_charge: { required: false, aliases: ["mass_charge", "mass charge"] },
  volume_charge: { required: false, aliases: ["volume_charge", "volume charge"] },
  charge_basis: { required: false, aliases: ["charge_basis", "charge basis", "basis"] },
  vat_include: { required: false, aliases: ["vat_include", "vat include"] },
  include_sad500: { required: false, aliases: ["include_sad500", "include sad500"] },
  include_sadc: { required: false, aliases: ["include_sadc", "include sadc"] },
  tracking_number: { required: false, aliases: ["tracking_number", "tracking number"] },
  status: { required: false, aliases: ["status"] },
  created_by: { required: false, aliases: ["created_by", "created by"] },
  created_at: { required: false, aliases: ["created_at", "created at"] },
  last_updated_by: { required: false, aliases: ["last_updated_by", "last updated by"] },
  last_updated_at: { required: false, aliases: ["last_updated_at", "last updated at"] },
  /** Serialized blob compatible with PHP serialize() / maybe_unserialize() (same shape as KIT_Waybills::prepareMiscCharges). */
  miscellaneous: { required: false, aliases: ["miscellaneous", "misc", "misc_serialized"] },
  misc_total: { required: false, aliases: ["misc_total", "misc total"] }
};

function syncAllWaybillData() {
  var startedAt = new Date();
  var summary = {
    run_started_at: startedAt.toISOString(),
    waybills: {},
    customers: {},
    run_finished_at: "",
    duration_ms: 0
  };

  withLock_(function () {
    var waybillsResult = syncWaybillsStage_();
    summary.waybills = waybillsResult.summary;
    summary.customers = syncCustomersFromKitWaybillsStage_(waybillsResult.targetIndex);
  });

  summary.run_finished_at = new Date().toISOString();
  summary.duration_ms = new Date().getTime() - startedAt.getTime();
  logSummary_(summary);
  return summary;
}

function syncKitWaybills() {
  return syncAllWaybillData();
}

function syncKitWaybillsOnly() {
  var out = {};
  withLock_(function () {
    out = syncWaybillsStage_().summary;
  });
  return out;
}

function syncCustomersFromKitWaybills() {
  var out = {};
  withLock_(function () {
    out = syncCustomersFromKitWaybillsStage_(null);
  });
  return out;
}

// Backward-compatibility alias for old trigger/function name.
function syncNewCustomersFromWaybills() {
  return syncCustomersFromKitWaybills();
}

function onEdit(e) {
  if (!CFG.enableOnEdit || !e || !e.range || !e.range.getSheet()) {
    return;
  }
  if (e.range.getSheet().getName() === CFG.sourceSheetName) {
    syncAllWaybillData();
  }
}

function onChange(e) {
  if (!CFG.enableOnChange) {
    return;
  }
  syncAllWaybillData();
}

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu("Waybill Sync")
    .addItem("Run Full Sync Now", "syncAllWaybillData")
    .addItem("Run Waybills Only", "syncKitWaybillsOnly")
    .addItem("Run Customers Only", "syncCustomersFromKitWaybills")
    .addItem("Repair Corrupted Customers", "repairKitCustomersFromKitWaybills")
    .addItem("Repair Missing Customer Formulas", "repairMissingCustomerFormulas")
    .addToUi();
}

function syncWaybillsStage_() {
  var startedAt = new Date();
  var summary = {
    run_started_at: startedAt.toISOString(),
    source_rows_read: 0,
    target_rows_read: 0,
    output_rows: 0,
    skipped_rows: 0,
    missing_required_source_fields: [],
    missing_required_target_fields: [],
    row_errors: [],
    run_finished_at: "",
    duration_ms: 0
  };

  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sourceSheet = ss.getSheetByName(CFG.sourceSheetName);
  var targetSheet = ss.getSheetByName(CFG.targetSheetName);
  if (!sourceSheet || !targetSheet) {
    throw new Error("Missing required sheet(s): " + CFG.sourceSheetName + " and/or " + CFG.targetSheetName);
  }

  var sourceData = sourceSheet.getDataRange().getValues();
  var targetData = targetSheet.getDataRange().getValues();
  summary.source_rows_read = Math.max(0, sourceData.length - 1);
  summary.target_rows_read = Math.max(0, targetData.length - 1);

  if (!targetData || targetData.length < 1) {
    throw new Error("Target sheet must have header row.");
  }

  var sourceHeaders = sourceData[0] || [];
  var targetHeaders = targetData[0] || [];
  var targetNumCols = targetHeaders.length;
  var sourceIndex = buildResolvedMap_(sourceHeaders, SRC);
  var targetIndex = buildResolvedMap_(targetHeaders, TGT);
  var lookup = buildLookupContext_(ss);

  var preflight = preflightValidation_(sourceIndex, targetIndex);
  summary.missing_required_source_fields = preflight.missingSource;
  summary.missing_required_target_fields = preflight.missingTarget;
  if (!preflight.ok) {
    throw new Error(preflight.message);
  }

  if (sourceData.length < 2) {
    clearTargetData_(targetSheet, targetNumCols);
    summary.run_finished_at = new Date().toISOString();
    summary.duration_ms = new Date().getTime() - startedAt.getTime();
    writeRowErrors_(ss, summary.row_errors);
    logSummary_(summary);
    return { summary: summary, targetIndex: targetIndex };
  }

  var existing = buildExistingWaybillState_(targetData, targetIndex);
  var output = [];

  for (var r = 1; r < sourceData.length; r++) {
    var srcRow = sourceData[r];
    var sourceRowNum = r + 1;
    var mapped = mapSourceRowToWaybillRecord_(srcRow, sourceRowNum, sourceIndex, targetIndex, targetHeaders, existing, lookup, summary);
    if (mapped) {
      output.push(mapped);
    }
  }

  clearTargetData_(targetSheet, targetNumCols);
  if (output.length > 0) {
    targetSheet.getRange(CFG.dataStartRow, 1, output.length, targetNumCols).setValues(output);
  }

  summary.output_rows = output.length;
  summary.run_finished_at = new Date().toISOString();
  summary.duration_ms = new Date().getTime() - startedAt.getTime();
  writeRowErrors_(ss, summary.row_errors);
  logSummary_(summary);

  return { summary: summary, targetIndex: targetIndex };
}

function syncCustomersFromKitWaybillsStage_(knownTargetIndex) {
  var startedAt = new Date();
  var summary = {
    run_started_at: startedAt.toISOString(),
    source_rows_read: 0,
    existing_customers: 0,
    appended_customers: 0,
    skipped_no_name: 0,
    skipped_suspicious_name: 0,
    skipped_existing: 0,
    backfilled_waybill_customer_ids: 0,
    repaired_formula_cells: 0,
    run_finished_at: "",
    duration_ms: 0
  };

  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var waybillSheet = ss.getSheetByName(CFG.targetSheetName);
  var waybillsSourceSheet = ss.getSheetByName(CFG.sourceSheetName);
  var customerSheet = ss.getSheetByName(CFG.customersSheetName);
  var citySheet = ss.getSheetByName(CFG.citiesSheetName);
  if (!waybillSheet || !customerSheet) {
    throw new Error("Missing required sheet(s): " + CFG.targetSheetName + " and/or " + CFG.customersSheetName);
  }

  var wb = waybillSheet.getDataRange().getValues();
  var cu = customerSheet.getDataRange().getValues();
  if (!wb || wb.length < 1 || !cu || cu.length < 1) {
    return summary;
  }

  var wbHeaders = wb[0];
  var wbIdx = knownTargetIndex || buildResolvedMap_(wbHeaders, TGT);
  var cityRows = citySheet ? citySheet.getDataRange().getValues() : [];
  var cityMap = buildCityNameMap_(cityRows);
  var cuIdx = buildCustomerTargetIndexes_(cu[0]);

  if (cuIdx.id < 0 || cuIdx.custId < 0 || cuIdx.name < 0 || cuIdx.surname < 0 || cuIdx.combined < 0 || cuIdx.company < 0) {
    throw new Error("kit_customers missing required columns for append-only sync.");
  }

  var existing = buildExistingCustomerState_(cu, cuIdx);
  summary.existing_customers = existing.count;
  summary.source_rows_read = Math.max(0, wb.length - 1);

  var contactByCustomerName = buildWaybillsCustomerContactMap_(waybillsSourceSheet);

  var pending = [];
  for (var i = 1; i < wb.length; i++) {
    var row = wb[i];
    var custName = cleanText_(valueByField_(row, wbIdx, "cust_name_ignore"));
    var custNorm = normalizeName_(custName);
    if (!custNorm || isPlaceholderName_(custNorm)) {
      summary.skipped_no_name++;
      continue;
    }
    if (isSuspiciousCustomerName_(custName)) {
      summary.skipped_suspicious_name++;
      continue;
    }

    var existingCustomerId = toPositiveId_(valueByField_(row, wbIdx, "customer_id"));
    var key = dedupeKey_(custName);

    if (key && (existing.keys[key] || existing.pendingKeys[key] || existingCustomerId !== "")) {
      summary.skipped_existing++;
      continue;
    }

    var cityId = toPositiveId_(valueByField_(row, wbIdx, "city_id"));
    var cityIdResolved = cityId !== "" ? cityId : (cityMap[normalizeName_(cleanText_(valueByField_(row, wbIdx, "city_id")))] || "");
    var split = splitPersonName_(custName);
    var contact = contactByCustomerName[custNorm] || {};

    existing.maxId += 1;
    existing.maxCustId = Math.max(existing.maxCustId + 1, CFG.customerSeedMinId);
    var newCustId = existing.maxCustId;

    var out = new Array(cu[0].length).fill("");
    out[cuIdx.id] = existing.maxId;
    out[cuIdx.custId] = newCustId;
    out[cuIdx.name] = split.name;
    out[cuIdx.surname] = split.surname;
    out[cuIdx.combined] = custName;
    if (cuIdx.country >= 0) out[cuIdx.country] = CFG.countryIdDefault;
    if (cuIdx.city >= 0) out[cuIdx.city] = cityIdResolved;
    if (cuIdx.vat >= 0) out[cuIdx.vat] = "N/A";
    if (cuIdx.address >= 0) out[cuIdx.address] = cleanText_(contact.address);
    if (cuIdx.cell >= 0) out[cuIdx.cell] = cleanText_(contact.cell);
    if (cuIdx.telephone >= 0) out[cuIdx.telephone] = cleanText_(contact.telephone);
    if (cuIdx.email >= 0) out[cuIdx.email] = cleanText_(contact.email);
    if (cuIdx.company >= 0) out[cuIdx.company] = "Private";
    if (cuIdx.created >= 0) out[cuIdx.created] = new Date();

    pending.push(out);
    if (key) {
      existing.pendingKeys[key] = true;
      existing.newIdsByKey[key] = newCustId;
    }
  }

  if (pending.length > 0) {
    var appendStartRow = customerSheet.getLastRow() + 1;
    var preserveCols = {
      id: cuIdx.id,
      custId: cuIdx.custId,
      combined: cuIdx.combined
    };
    var templateFormulasR1C1 = resolveTemplateFormulasR1C1_(customerSheet, appendStartRow, cu[0].length, preserveCols);

    customerSheet.getRange(appendStartRow, 1, pending.length, cu[0].length).setValues(pending);
    applyTemplateFormulasToAppendedRows_(customerSheet, appendStartRow, pending.length, templateFormulasR1C1, preserveCols);
    summary.appended_customers = pending.length;
  }

  summary.backfilled_waybill_customer_ids = backfillWaybillCustomerIds_(waybillSheet, wb, wbIdx, existing.newIdsByKey);
  summary.repaired_formula_cells = repairMissingCustomerFormulasCore_(customerSheet, cuIdx);
  summary.run_finished_at = new Date().toISOString();
  summary.duration_ms = new Date().getTime() - startedAt.getTime();
  logSummary_(summary);
  return summary;
}

function backfillWaybillCustomerIds_(sheet, wbData, wbIdx, newIdsByKey) {
  var customerColIdx = wbIdx.customer_id;
  var nameColIdx = wbIdx.cust_name_ignore;
  if (customerColIdx === undefined || customerColIdx < 0 || nameColIdx === undefined || nameColIdx < 0) {
    return 0;
  }

  var changed = 0;
  var outCol = [];
  for (var i = 1; i < wbData.length; i++) {
    var existingId = toPositiveId_(wbData[i][customerColIdx]);
    var name = cleanText_(wbData[i][nameColIdx]);
    var key = dedupeKey_(name);
    var resolved = key ? (newIdsByKey[key] || "") : "";
    if (existingId === "" && resolved !== "") {
      outCol.push([resolved]);
      changed++;
    } else {
      outCol.push([wbData[i][customerColIdx]]);
    }
  }

  if (outCol.length > 0 && changed > 0) {
    sheet.getRange(CFG.dataStartRow, customerColIdx + 1, outCol.length, 1).setValues(outCol);
  }
  return changed;
}

function mapSourceRowToWaybillRecord_(row, sourceRowNum, sourceIdx, targetIdx, targetHeaders, existing, lookup, summary) {
  var colAText = valueFromWaybillsColumnA_(row);
  if (shouldSkipWaybillByWaybillsColumnA_(colAText)) {
    summary.skipped_rows++;
    pushRowError_(summary, sourceRowNum, "Skipped: Waybills column A is duplicate/cancelled (" + cleanText_(colAText) + ").");
    return null;
  }

  var waybillNo = extractWaybillNo_(cleanText_(valueByField_(row, sourceIdx, "waybillNo"))) || extractWaybillNo_(cleanText_(valueByField_(row, sourceIdx, "parcelText")));
  if (!waybillNo) {
    summary.skipped_rows++;
    pushRowError_(summary, sourceRowNum, "Missing parseable waybill number.");
    return null;
  }

  var id = existing.idsByWaybill[waybillNo];
  if (!id) {
    existing.maxId++;
    id = existing.maxId;
    existing.idsByWaybill[waybillNo] = id;
  }

  var link = existing.linksByWaybill[waybillNo] || {};
  // Per client (Mel Ltd, 2026-05-19): the waybill MUST always pull "Waybill description",
  // never "Item description". Prioritise waybillDesc; fall back to itemDesc only when the
  // waybill description column is empty so we don't leave the field blank for legacy rows.
  var description = cleanText_(valueByField_(row, sourceIdx, "waybillDesc")) || cleanText_(valueByField_(row, sourceIdx, "itemDesc"));
  var customerName = cleanText_(valueByField_(row, sourceIdx, "customer"));
  var deliveryId = pickPositiveId_(valueByField_(row, sourceIdx, "deliveryId"), link.delivery_id);
  var directionId = pickPositiveId_(valueByField_(row, sourceIdx, "directionId"), link.direction_id, deliveryId > 0 ? lookup.deliveryDirectionById[deliveryId] : "", CFG.defaultDirectionId);
  var cityName = cleanText_(valueByField_(row, sourceIdx, "cityName"));
  var cityId = pickPositiveId_(valueByField_(row, sourceIdx, "cityId"), link.city_id, deliveryId > 0 ? lookup.deliveryCityById[deliveryId] : "", lookupCityIdByName_(lookup.cityIdByName, cityName));
  var customerId = pickPositiveId_(valueByField_(row, sourceIdx, "customerId"), link.customer_id, lookupCustomerIdByName_(lookup.customerIdByName, customerName));

  var record = new Array(targetHeaders.length).fill("");
  setByField_(record, targetIdx, "id", id);
  setByField_(record, targetIdx, "waybill_no", waybillNo);
  setByField_(record, targetIdx, "description", description);
  setParcelId_(record, targetHeaders, targetIdx, waybillNo);
  setByField_(record, targetIdx, "delivery_id", deliveryId > 0 ? deliveryId : "");
  setByField_(record, targetIdx, "direction_id", directionId > 0 ? directionId : CFG.defaultDirectionId);
  setByField_(record, targetIdx, "city_id", cityId > 0 ? cityId : "");
  setByField_(record, targetIdx, "customer_id", customerId > 0 ? customerId : "");
  setByField_(record, targetIdx, "approval", firstNonEmpty_(cleanText_(valueByField_(row, sourceIdx, "approval")), link.approval));
  setByField_(record, targetIdx, "approval_userid", firstNonEmpty_(toPositiveId_(valueByField_(row, sourceIdx, "approvalUserId")), link.approval_userid));
  setByField_(record, targetIdx, "cust_name_ignore", customerName);
  setByField_(record, targetIdx, "product_invoice_number", resolveProductInvoiceNumber_(row, sourceIdx, link, existing));
  setByField_(record, targetIdx, "product_invoice_amount", parseNumber_(valueByField_(row, sourceIdx, "custInvR")));
  setByField_(record, targetIdx, "sad500_amount", parseNumber_(valueByField_(row, sourceIdx, "sad500")));
  setByField_(record, targetIdx, "sadc_amount", parseNumber_(valueByField_(row, sourceIdx, "sadc")));
  setByField_(record, targetIdx, "item_length", parseNumber_(valueByField_(row, sourceIdx, "length")));
  setByField_(record, targetIdx, "item_width", parseNumber_(valueByField_(row, sourceIdx, "width")));
  setByField_(record, targetIdx, "item_height", parseNumber_(valueByField_(row, sourceIdx, "height")));
  setByField_(record, targetIdx, "total_mass_kg", parseNumber_(valueByField_(row, sourceIdx, "tMass")));
  setByField_(record, targetIdx, "total_volume", parseNumber_(valueByField_(row, sourceIdx, "tVol")));
  setByField_(record, targetIdx, "mass_charge", parseNumber_(valueByField_(row, sourceIdx, "massCost")));
  setByField_(record, targetIdx, "volume_charge", parseNumber_(valueByField_(row, sourceIdx, "volCost")));
  setByField_(record, targetIdx, "charge_basis", cleanText_(valueByField_(row, sourceIdx, "basis")));

  // VAT / SAD500 / SADC flag resolution.
  //
  // Source sheet uses three independent columns (VAT, SAD500, SADC) that any of:
  //   - boolean (sheets checkbox / true / false)
  //   - text "TRUE"/"FALSE"/"YES"/"NO"/"Y"/"N"/"1"/"0"
  //   - numeric rand amount (positive number == flag on)
  // Legacy single-column shape ("VAT" cell contains the literal text "SAD500" or "SADC")
  // still wins for the legacy flag so historic rows keep mapping the way they used to.
  // Each flag is otherwise driven by its own dedicated column via parseBooleanFlexible_,
  // so a row with VAT=TRUE + SAD500=TRUE in separate columns now produces vat_include=1
  // AND include_sad500=1 instead of silently dropping SAD500.
  var vatRaw = valueByField_(row, sourceIdx, "vat");
  var legacyVat = String(vatRaw).trim().toUpperCase();
  var vatInclude = (legacyVat === "SAD500" || legacyVat === "SADC") ? false : parseBooleanFlexible_(vatRaw);
  setByField_(record, targetIdx, "vat_include", vatInclude);
  setByField_(record, targetIdx, "include_sad500", legacyVat === "SAD500" ? true : parseBooleanFlexible_(valueByField_(row, sourceIdx, "sad500")));
  setByField_(record, targetIdx, "include_sadc", legacyVat === "SADC" ? true : parseBooleanFlexible_(valueByField_(row, sourceIdx, "sadc")));
  var trackingFromSheet = cleanText_(valueByField_(row, sourceIdx, "tracking"));
  var trackingMerged = firstNonEmpty_(trackingFromSheet, cleanText_(link.tracking_number));
  setByField_(record, targetIdx, "tracking_number", trackingMerged || generateTrackingNumber_());
  setByField_(record, targetIdx, "status", cleanText_(valueByField_(row, sourceIdx, "status")));

  // CL INV # / Waybills:AA = customer's own purchase invoice ref (not our charge). Stored only in miscellaneous.others.client_invoice.
  var custInvRef = resolveCustomerPurchaseInvoiceRef_(row, sourceIdx);
  var miscSerialized = buildSerializedMiscellaneousCustomerInvoiceRef_(custInvRef);
  if (miscSerialized) {
    setByField_(record, targetIdx, "miscellaneous", miscSerialized);
    setByField_(record, targetIdx, "misc_total", 0);
  }

  var nowTs = currentMysqlTimestamp_();
  var auditUid = pickWaybillAuditUserId_(row, sourceIdx, link);
  var createdFromWaybills = normalizeSheetDateTime_(valueByField_(row, sourceIdx, "dateReceived"));
  var createdAtExisting = normalizeSheetDateTime_(link.created_at);
  var createdByExisting = toPositiveId_(link.created_by);
  setByField_(record, targetIdx, "created_at", createdFromWaybills || createdAtExisting || nowTs);
  setByField_(record, targetIdx, "created_by", createdByExisting !== "" ? createdByExisting : auditUid);
  setByField_(record, targetIdx, "last_updated_at", nowTs);
  setByField_(record, targetIdx, "last_updated_by", auditUid !== "" ? auditUid : createdByExisting);

  setKitWaybillWarehouseColumnAc_(record, isWarehouseFromWaybillsColumnA_(colAText));

  if (cityId <= 0) {
    pushRowError_(summary, sourceRowNum, "Could not resolve city_id for waybill " + waybillNo + (cityName ? (" (city: " + cityName + ")") : ""));
  }
  return record;
}

function buildExistingWaybillState_(targetData, targetIdx) {
  var state = { idsByWaybill: {}, linksByWaybill: {}, maxId: 0 };
  for (var i = 1; i < targetData.length; i++) {
    var row = targetData[i];
    var wb = extractWaybillNo_(cleanText_(valueByField_(row, targetIdx, "waybill_no")));
    var id = Number(valueByField_(row, targetIdx, "id"));
    id = isNaN(id) ? 0 : id;
    if (wb) {
      state.idsByWaybill[wb] = id;
      state.linksByWaybill[wb] = {
        direction_id: valueByField_(row, targetIdx, "direction_id"),
        city_id: valueByField_(row, targetIdx, "city_id"),
        delivery_id: valueByField_(row, targetIdx, "delivery_id"),
        customer_id: valueByField_(row, targetIdx, "customer_id"),
        approval: valueByField_(row, targetIdx, "approval"),
        approval_userid: valueByField_(row, targetIdx, "approval_userid"),
        tracking_number: valueByField_(row, targetIdx, "tracking_number"),
        product_invoice_number: valueByField_(row, targetIdx, "product_invoice_number"),
        created_by: valueByField_(row, targetIdx, "created_by"),
        created_at: valueByField_(row, targetIdx, "created_at"),
        last_updated_by: valueByField_(row, targetIdx, "last_updated_by"),
        last_updated_at: valueByField_(row, targetIdx, "last_updated_at")
      };
    }
    if (id > state.maxId) {
      state.maxId = id;
    }
  }
  var tz = Session.getScriptTimeZone();
  var datePrefix = Utilities.formatDate(new Date(), tz, "yyyyMMdd");
  state.invoicePattern = "INV-" + datePrefix + "-";
  var maxSeq = 0;
  for (var j = 1; j < targetData.length; j++) {
    var pin = cleanText_(valueByField_(targetData[j], targetIdx, "product_invoice_number"));
    if (pin.indexOf(state.invoicePattern) !== 0) continue;
    var parts = pin.split("-");
    var lastPart = parts[parts.length - 1];
    var n = parseInt(lastPart, 10);
    if (!isNaN(n) && n > maxSeq) maxSeq = n;
  }
  state.invoiceNextSeq = maxSeq + 1;
  return state;
}

function buildExistingCustomerState_(cuData, cuIdx) {
  var out = { keys: {}, pendingKeys: {}, newIdsByKey: {}, maxId: 0, maxCustId: 0, count: Math.max(0, cuData.length - 1) };
  for (var i = 1; i < cuData.length; i++) {
    var row = cuData[i];
    var id = Number(row[cuIdx.id]) || 0;
    var custId = Number(row[cuIdx.custId]) || 0;
    if (id > out.maxId) out.maxId = id;
    if (custId > out.maxCustId) out.maxCustId = custId;
    var key = dedupeKey_(row[cuIdx.combined]);
    if (key) out.keys[key] = true;
  }
  return out;
}

function buildCustomerTargetIndexes_(headers) {
  var idx = {
    id: colIndexExact_(headers, ["id"]),
    custId: colIndexExact_(headers, ["cust_id"]),
    name: colIndexExact_(headers, ["name"]),
    surname: colIndexExact_(headers, ["surname"]),
    combined: colIndexExact_(headers, ["combined_name_ignore"]),
    country: colIndexExact_(headers, ["country_id"]),
    city: colIndexExact_(headers, ["city_id"]),
    vat: colIndexExact_(headers, ["vat_number"]),
    address: colIndexExact_(headers, ["address"]),
    cell: colIndexExact_(headers, ["cell", "cellphone", "mobile", "mobile_number", "mobile number"]),
    telephone: colIndexExact_(headers, ["telephone", "tel", "tel_number", "tel number", "phone", "phone_number", "phone number"]),
    email: colIndexExact_(headers, ["email_address", "email", "e-mail"]),
    company: colIndexExact_(headers, ["company_name"]),
    created: colIndexExact_(headers, ["created_at"])
  };

  // Fallback to alias matching only if strict exact fails.
  if (idx.id >= 0 && idx.custId >= 0 && idx.name >= 0 && idx.surname >= 0 && idx.combined >= 0 && idx.company >= 0) {
    return idx;
  }
  return {
    id: colIndex_(headers, ["id"]),
    custId: colIndex_(headers, ["cust_id"]),
    name: colIndex_(headers, ["name"]),
    surname: colIndex_(headers, ["surname"]),
    combined: colIndex_(headers, ["combined_name_ignore"]),
    country: colIndex_(headers, ["country_id"]),
    city: colIndex_(headers, ["city_id"]),
    vat: colIndex_(headers, ["vat_number"]),
    address: colIndex_(headers, ["address"]),
    cell: colIndex_(headers, ["cell", "cellphone", "mobile", "mobile_number", "mobile number"]),
    telephone: colIndex_(headers, ["telephone", "tel", "tel_number", "tel number", "phone", "phone_number", "phone number"]),
    email: colIndex_(headers, ["email_address", "email", "e-mail"]),
    company: colIndex_(headers, ["company_name"]),
    created: colIndex_(headers, ["created_at"])
  };
}

function buildWaybillsCustomerContactMap_(waybillsSourceSheet) {
  var out = {};
  if (!waybillsSourceSheet) return out;
  var data = waybillsSourceSheet.getDataRange().getValues();
  if (!data || data.length < 2) return out;

  var headers = data[0] || [];
  var idxCustomer = colIndex_(headers, ["customer", "cust name", "client", "customer name"]);
  var idxCell = colIndex_(headers, ["cell", "cellphone", "mobile", "mobile number", "mobile_number"]);
  var idxTelephone = colIndex_(headers, ["telephone", "tel", "phone", "phone number", "phone_number"]);
  var idxEmail = colIndex_(headers, ["email", "email address", "email_address", "e-mail"]);
  var idxAddress = colIndex_(headers, ["address", "physical address", "postal address"]);

  if (idxCustomer < 0) return out;

  for (var r = 1; r < data.length; r++) {
    var row = data[r];
    var nameRaw = cleanText_(row[idxCustomer]);
    var nameKey = normalizeName_(nameRaw);
    if (!nameKey) continue;

    var existing = out[nameKey] || {};
    var cell = idxCell >= 0 ? cleanText_(row[idxCell]) : "";
    var tel = idxTelephone >= 0 ? cleanText_(row[idxTelephone]) : "";
    var email = idxEmail >= 0 ? cleanText_(row[idxEmail]) : "";
    var addr = idxAddress >= 0 ? cleanText_(row[idxAddress]) : "";

    if (cell) existing.cell = cell;
    if (tel) existing.telephone = tel;
    if (email) existing.email = email;
    if (addr) existing.address = addr;

    out[nameKey] = existing;
  }
  return out;
}

function repairKitCustomersFromKitWaybills() {
  var result = {};
  withLock_(function () {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sh = ss.getSheetByName(CFG.customersSheetName);
    if (!sh) {
      throw new Error("Missing required sheet: " + CFG.customersSheetName);
    }
    var data = sh.getDataRange().getValues();
    if (!data || data.length < 2) {
      result = syncCustomersFromKitWaybillsStage_(null);
      return;
    }

    var headers = data[0];
    var idx = buildCustomerTargetIndexes_(headers);
    if (idx.id < 0 || idx.custId < 0 || idx.name < 0 || idx.surname < 0 || idx.combined < 0) {
      throw new Error("kit_customers headers are invalid; cannot run repair safely.");
    }

    var kept = [headers];
    var removed = 0;
    for (var i = 1; i < data.length; i++) {
      if (isLikelyCorruptedCustomerRow_(data[i], idx)) {
        removed++;
        continue;
      }
      kept.push(data[i]);
    }

    if (removed > 0) {
      sh.clearContents();
      sh.getRange(1, 1, kept.length, headers.length).setValues(kept);
    }

    result = syncCustomersFromKitWaybillsStage_(null);
    result.repair_removed_rows = removed;
  });
  return result;
}

function repairMissingCustomerFormulas() {
  var result = { repaired_formula_cells: 0 };
  withLock_(function () {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var customerSheet = ss.getSheetByName(CFG.customersSheetName);
    if (!customerSheet) {
      throw new Error("Missing required sheet: " + CFG.customersSheetName);
    }
    var data = customerSheet.getDataRange().getValues();
    if (!data || data.length < 2) {
      return;
    }
    var cuIdx = buildCustomerTargetIndexes_(data[0]);
    result.repaired_formula_cells = repairMissingCustomerFormulasCore_(customerSheet, cuIdx);
  });
  return result;
}

function withLock_(fn) {
  var lock = LockService.getDocumentLock();
  if (!lock.tryLock(CFG.lockTimeoutMs)) {
    // Another sync is already in progress. Skip silently instead of throwing — the
    // next trigger (or manual run) will pick up where this one left off. Throwing
    // here just spams the Apps Script failure log with "Sync lock timeout" errors
    // every time a change/edit trigger fires while the previous sync is still running
    // (very common with IMPORTRANGE-fed source sheets that refresh frequently).
    Logger.log("withLock_: skipped — another sync is in progress.");
    return;
  }
  try {
    fn();
  } finally {
    lock.releaseLock();
  }
}

function preflightValidation_(sourceIndex, targetIndex) {
  var missingSource = [];
  var missingTarget = [];
  Object.keys(SRC).forEach(function (k) { if (SRC[k].required && sourceIndex[k] < 0) missingSource.push(k); });
  Object.keys(TGT).forEach(function (k) { if (TGT[k].required && targetIndex[k] < 0) missingTarget.push(k); });
  if (missingSource.length || missingTarget.length) {
    return { ok: false, missingSource: missingSource, missingTarget: missingTarget, message: "Preflight failed. Missing required mappings. Source: [" + missingSource.join(", ") + "] Target: [" + missingTarget.join(", ") + "]" };
  }
  return { ok: true, missingSource: [], missingTarget: [], message: "" };
}

function buildResolvedMap_(headers, schema) {
  var out = {};
  Object.keys(schema).forEach(function (key) { out[key] = colIndex_(headers, schema[key].aliases); });
  return out;
}

function valueByField_(row, indexMap, field) {
  var idx = indexMap[field];
  if (idx === undefined || idx < 0) return "";
  return row[idx];
}

function setByField_(row, indexMap, field, value) {
  var idx = indexMap[field];
  if (idx === undefined || idx < 0) return;
  row[idx] = (value === null || value === undefined) ? "" : value;
}

function setParcelId_(record, targetHeaders, targetIndex, waybillNo) {
  var idx = targetIndex.parcel_id;
  if (idx !== undefined && idx >= 0) {
    record[idx] = waybillNo;
    return;
  }
  for (var i = 0; i < targetHeaders.length; i++) {
    var h = normHeader_(targetHeaders[i]);
    if (h.indexOf("parcel") !== -1 && h.indexOf("id") !== -1) {
      record[i] = waybillNo;
      return;
    }
  }
  if (targetHeaders.length > 1) {
    record[1] = waybillNo;
  }
}

function clearTargetData_(sheet, numCols) {
  var clearRows = 0;
  if (CFG.clearThroughRow > 0) {
    clearRows = Math.max(0, CFG.clearThroughRow - CFG.dataStartRow + 1);
  } else {
    clearRows = Math.max(0, sheet.getLastRow() - CFG.dataStartRow + 1);
  }
  if (clearRows > 0 && numCols > 0) {
    sheet.getRange(CFG.dataStartRow, 1, clearRows, numCols).clearContent();
  }
}

function pushRowError_(summary, sourceRowNum, message) {
  summary.row_errors.push({ when: new Date().toISOString(), source_row: sourceRowNum, message: message });
}

function writeRowErrors_(ss, errors) {
  if (!CFG.writeErrorSheet || !errors || errors.length === 0) return;
  var sh = ss.getSheetByName(CFG.errorSheetName);
  if (!sh) {
    sh = ss.insertSheet(CFG.errorSheetName);
    sh.getRange(1, 1, 1, 3).setValues([["timestamp", "source_row", "message"]]);
  }
  var rows = errors.map(function (e) { return [e.when, e.source_row, e.message]; });
  sh.getRange(sh.getLastRow() + 1, 1, rows.length, 3).setValues(rows);
}

function logSummary_(summary) {
  Logger.log(JSON.stringify(summary));
}

/**
 * Normalises a sheet header cell so it can be compared against an alias list.
 * Coerces non-strings to string, converts non-breaking spaces to regular spaces,
 * trims, lowercases, and collapses any run of whitespace to a single space — same
 * shape the SRC/TGT alias lists are written in (e.g. "waybill no.", "cl inv #",
 * "t mass"). Restored after a runtime "normHeader_ is not defined" ReferenceError:
 * the previous declaration was misspelled (normHeafder_) so the real identifier
 * was never bound even though colIndex_/setParcelId_ called it everywhere.
 */
function normHeader_(h) {
  return String(h || "").replace(/\u00a0/g, " ").trim().toLowerCase().replace(/\s+/g, " ");
}

function colIndex_(headers, aliases) {
  var normalized = headers.map(function (h) { return normHeader_(h); });
  var a, i, n;
  for (a = 0; a < aliases.length; a++) {
    n = normHeader_(aliases[a]);
    if (!n) continue;
    for (i = 0; i < normalized.length; i++) {
      if (normalized[i] === n) return i;
    }
  }
  for (a = 0; a < aliases.length; a++) {
    n = normHeader_(aliases[a]);
    if (n.length < 3) continue;
    for (i = 0; i < normalized.length; i++) {
      if (normalized[i] && (normalized[i].indexOf(n) !== -1 || n.indexOf(normalized[i]) !== -1)) return i;
    }
  }
  return -1;
}

function colIndexExact_(headers, aliases) {
  var normalized = headers.map(function (h) { return normHeader_(h); });
  for (var a = 0; a < aliases.length; a++) {
    var n = normHeader_(aliases[a]);
    if (!n) continue;
    for (var i = 0; i < normalized.length; i++) {
      if (normalized[i] === n) return i;
    }
  }
  return -1;
}

function extractWaybillNo_(raw) {
  var s = cleanText_(raw);
  if (!s) return "";
  var m1 = s.match(/wb[:\s-]*(\d+)/i);
  if (m1) return m1[1];
  var m2 = s.match(/^(\d{4,})\b/);
  if (m2) return m2[1];
  var digits = s.replace(/\D/g, "");
  return digits.length >= 3 ? digits : "";
}

/**
 * Matches PHP: 'TRK-' . strtoupper(wp_generate_password(8, false, false))
 * (8 uppercase alphanumeric characters after TRK-).
 */
function generateTrackingNumber_() {
  var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  var suffix = "";
  for (var i = 0; i < 8; i++) {
    suffix += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return "TRK-" + suffix;
}

/**
 * Matches KIT_Waybills::generate_product_invoice_number() for rows on this sheet:
 * INV-YYYYMMDD-##### (5-digit sequence). Requires existing.invoicePattern and existing.invoiceNextSeq.
 */
function allocateProductInvoiceNumber_(existing) {
  var seq = existing.invoiceNextSeq;
  existing.invoiceNextSeq = seq + 1;
  var pad = "00000" + seq;
  return existing.invoicePattern + pad.slice(-5);
}

/**
 * Resolves product_invoice_number from CFG.productInvoiceNumberMode and productInvoiceUseWaybillsSheet.
 */
function resolveProductInvoiceNumber_(row, sourceIdx, link, existing) {
  var mode = String(CFG.productInvoiceNumberMode || "preserve").toLowerCase();
  var useWaybills = CFG.productInvoiceUseWaybillsSheet === true;
  var invFromSheet = useWaybills ? cleanText_(valueByField_(row, sourceIdx, "clInv")) : "";
  if (mode === "always_allocate") {
    return allocateProductInvoiceNumber_(existing);
  }
  if (mode === "sheet_or_allocate") {
    if (useWaybills) {
      return invFromSheet || allocateProductInvoiceNumber_(existing);
    }
    return allocateProductInvoiceNumber_(existing);
  }
  // preserve: optional Waybills clInv → existing kit row → allocate
  var invMerged = useWaybills
    ? firstNonEmpty_(invFromSheet, cleanText_(link.product_invoice_number))
    : cleanText_(link.product_invoice_number);
  return invMerged || allocateProductInvoiceNumber_(existing);
}

function cleanText_(value) {
  if (value === null || value === undefined) return "";
  return String(value).trim();
}

/** MySQL-style datetime string in the script timezone (matches WP current_time('mysql') shape). */
function currentMysqlTimestamp_() {
  return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyy-MM-dd HH:mm:ss");
}

/** Normalize Date or string from Sheets into yyyy-MM-dd HH:mm:ss for kit_waybills.created_at. */
function normalizeSheetDateTime_(value) {
  if (value === null || value === undefined || value === "") return "";
  if (Object.prototype.toString.call(value) === "[object Date]" && !isNaN(value.getTime())) {
    return Utilities.formatDate(value, Session.getScriptTimeZone(), "yyyy-MM-dd HH:mm:ss");
  }
  return cleanText_(value);
}

/** Numeric WP user id for audit columns when syncing from Apps Script. */
function pickWaybillAuditUserId_(row, sourceIdx, link) {
  var cfgUid = toPositiveId_(CFG.waybillSyncUserId);
  if (cfgUid !== "") return cfgUid;
  var rowUid = toPositiveId_(valueByField_(row, sourceIdx, "approvalUserId"));
  if (rowUid !== "") return rowUid;
  var linkAppr = toPositiveId_(link.approval_userid);
  if (linkAppr !== "") return linkAppr;
  return toPositiveId_(link.created_by);
}

function parseNumber_(value) {
  if (value === null || value === undefined || value === "") return "";
  var cleaned = String(value).replace(/[^\d.\-]/g, "");
  var num = Number(cleaned);
  return isNaN(num) ? "" : num;
}

function parseBoolean_(value) {
  var v = String(value).trim().toLowerCase();
  return v === "true" || v === "yes" || v === "1" || v === "y";
}

function parseBooleanFromAmount_(value) {
  var num = parseNumber_(value);
  return num !== "" && num > 0;
}

/**
 * Accepts the full range of truth markers that show up in the Waybills source sheet:
 *   - native booleans (e.g. Sheets checkbox cells return JS true/false)
 *   - case-insensitive text: "true"/"false", "yes"/"no", "y"/"n", "1"/"0"
 *   - positive numeric amounts (e.g. SAD500 rand amount > 0 means flag on)
 * Empty cells and explicit falsey strings/numbers map to false.
 * Used by the VAT / SAD500 / SADC flag mapping so a row that just toggles a checkbox
 * (rather than typing a rand amount) still flips include_sad500 / include_sadc /
 * vat_include on. Previously parseBooleanFromAmount_ only honoured positive numbers,
 * so checkbox/text TRUE values were silently treated as false during the sync.
 */
function parseBooleanFlexible_(value) {
  if (value === true) return true;
  if (value === false) return false;
  if (value === null || value === undefined) return false;
  var s = String(value).trim().toLowerCase();
  if (s === "") return false;
  if (s === "true" || s === "yes" || s === "y") return true;
  if (s === "false" || s === "no" || s === "n") return false;
  var num = parseNumber_(value);
  return num !== "" && num > 0;
}

/** Waybills column A (0): status / flags — "warehouse" text → kit_waybills column AC = 1; duplicate/cancelled → skip row. */
var SOURCE_COL_A_INDEX_ = 0;

/** kit_waybills column AC (1-based 29) = warehouse 1/0 — matches user sheet layout (Apps Script 0-based index 28). */
var KIT_WAYBILLS_WAREHOUSE_COL_AC_INDEX_ = 28;

/** 0-based column index for Google Sheets column AA (27th column). */
var SOURCE_COL_AA_INDEX_ = 26;

/**
 * Raw cell at Waybills column A (first column) — e.g. "drive warehouse", "Duplicate", "[Duplicate,Cancelled]".
 */
function valueFromWaybillsColumnA_(row) {
  if (!row || row.length <= SOURCE_COL_A_INDEX_) {
    return "";
  }
  return row[SOURCE_COL_A_INDEX_];
}

/** True if Waybills column A marks row as duplicate or cancelled (do not sync to kit_waybills). */
function shouldSkipWaybillByWaybillsColumnA_(colAText) {
  var s = cleanText_(colAText);
  if (s === "") {
    return false;
  }
  var low = s.toLowerCase();
  if (low.indexOf("duplicate") !== -1) {
    return true;
  }
  if (low.indexOf("cancelled") !== -1 || low.indexOf("canceled") !== -1) {
    return true;
  }
  return false;
}

/** True if column A indicates warehouse routing (e.g. "Warehouse", "drive warehouse"). */
function isWarehouseFromWaybillsColumnA_(colAText) {
  return /\bwarehouse\b/i.test(cleanText_(colAText));
}

/** Write warehouse flag to kit_waybills column AC (index 28) when the row is wide enough. */
function setKitWaybillWarehouseColumnAc_(record, isWarehouse) {
  if (!record || record.length <= KIT_WAYBILLS_WAREHOUSE_COL_AC_INDEX_) {
    return;
  }
  record[KIT_WAYBILLS_WAREHOUSE_COL_AC_INDEX_] = isWarehouse ? 1 : 0;
}

/**
 * Raw cell at Waybills column AA when the row array is wide enough (matches "Waybills!AA" style sources).
 */
function valueFromWaybillsColumnAa_(row) {
  if (!row || row.length <= SOURCE_COL_AA_INDEX_) {
    return "";
  }
  return row[SOURCE_COL_AA_INDEX_];
}

/**
 * Customer's own invoice / PO reference (Waybills "CL INV #" = column AA). Not a monetary line item.
 * Prefer header-mapped clInv, then physical column AA, then optional clientInvoiceAa alias.
 */
function resolveCustomerPurchaseInvoiceRef_(row, sourceIdx) {
  return firstNonEmpty_(
    cleanText_(valueByField_(row, sourceIdx, "clInv")),
    cleanText_(valueFromWaybillsColumnAa_(row)),
    cleanText_(valueByField_(row, sourceIdx, "clientInvoiceAa"))
  );
}

/** Sheet placeholders — not a real customer invoice reference. */
function isCustomerInvoiceRefPlaceholder_(ref) {
  var s = cleanText_(ref).toLowerCase();
  if (s === "") return true;
  if (s === "p" || s === "n/a" || s === "na" || s === "none" || s === "null" || s === "-" || s === "--") return true;
  return false;
}

/**
 * PHP serialize() subset for kit_waybills.miscellaneous:
 * a:3:{ misc_items[], misc_total float, others[] }
 * Each misc row matches KIT_Waybills::getMiscCharges: misc_item, misc_price, misc_quantity, realized.
 * Uses UTF-8 byte lengths for string payloads (PHP strlen semantics).
 */
function phpSerializeString_(str) {
  str = String(str);
  var escaped = str.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
  var byteLen = Utilities.newBlob(str).getBytes().length;
  return "s:" + byteLen + ':"' + escaped + '";';
}

function phpSerializeInt_(n) {
  var x = parseInt(n, 10);
  if (isNaN(x)) {
    x = 0;
  }
  return "i:" + x + ";";
}

function phpSerializeDouble_(n) {
  var x = typeof n === "number" ? n : parseFloat(n);
  if (isNaN(x)) {
    x = 0;
  }
  var s = String(x);
  if (s.indexOf("e") >= 0 || s.indexOf("E") >= 0) {
    s = x.toFixed(6).replace(/\.?0+$/, "");
    if (s === "" || s === "-") {
      s = "0";
    }
  }
  return "d:" + s + ";";
}

function phpSerializeBool_(v) {
  return v ? "b:1;" : "b:0;";
}

/**
 * @param {string} invoiceRef — customer's purchase invoice / ref (not our billing amount)
 * @return {string} PHP serialized miscellaneous or "" if empty/placeholder
 */
function buildSerializedMiscellaneousCustomerInvoiceRef_(invoiceRef) {
  var ref = cleanText_(invoiceRef);
  if (ref === "" || isCustomerInvoiceRefPlaceholder_(ref)) {
    return "";
  }
  if (ref.length > 255) {
    ref = ref.substring(0, 255);
  }
  var miscItems = "a:0:{}";
  var others = "a:1:{" + phpSerializeString_(OTHERS_KEY_CLIENT_INVOICE_) + phpSerializeString_(ref) + "}";
  return "a:3:{" +
    phpSerializeString_("misc_items") + miscItems +
    phpSerializeString_("misc_total") + phpSerializeDouble_(0) +
    phpSerializeString_("others") + others +
    "}";
}

function firstNonEmpty_(preferred, fallback) {
  if (preferred !== null && preferred !== undefined && String(preferred) !== "") return preferred;
  if (fallback !== null && fallback !== undefined && String(fallback) !== "") return fallback;
  return "";
}

function toPositiveId_(value) {
  if (value === null || value === undefined || value === "") return "";
  var cleaned = String(value).replace(/[^\d\-]/g, "");
  if (cleaned === "" || cleaned === "-") return "";
  var n = parseInt(cleaned, 10);
  return isNaN(n) || n <= 0 ? "" : n;
}

function pickPositiveId_() {
  for (var i = 0; i < arguments.length; i++) {
    var v = toPositiveId_(arguments[i]);
    if (v !== "") return v;
  }
  return 0;
}

function buildLookupContext_(ss) {
  return {
    deliveryDirectionById: buildDeliveryFieldMap_(ss, "direction_id"),
    deliveryCityById: buildDeliveryFieldMap_(ss, "destination_city_id"),
    customerIdByName: buildCustomerNameToIdMap_(ss),
    cityIdByName: buildCityNameToIdMap_(ss)
  };
}

function buildDeliveryFieldMap_(ss, wantedField) {
  var out = {};
  var sh = ss.getSheetByName(CFG.deliveriesSheetName);
  if (!sh) return out;
  var rows = sh.getDataRange().getValues();
  if (!rows || rows.length < 2) return out;
  var headers = rows[0];
  var idIdx = colIndex_(headers, ["id"]);
  var fieldIdx = colIndex_(headers, [wantedField, wantedField.replace(/_/g, " ")]);
  if (wantedField === "destination_city_id" && fieldIdx < 0) {
    fieldIdx = colIndex_(headers, ["city_id", "destination city id", "city id"]);
  }
  if (idIdx < 0 || fieldIdx < 0) return out;
  for (var i = 1; i < rows.length; i++) {
    var id = toPositiveId_(rows[i][idIdx]);
    var val = toPositiveId_(rows[i][fieldIdx]);
    if (id !== "" && val !== "") out[id] = val;
  }
  return out;
}

function buildCustomerNameToIdMap_(ss) {
  var out = {};
  var sh = ss.getSheetByName(CFG.customersSheetName);
  if (!sh) return out;
  var rows = sh.getDataRange().getValues();
  if (!rows || rows.length < 2) return out;
  var headers = rows[0];
  var idIdx = colIndex_(headers, ["cust_id", "customer_id", "customer id"]);
  if (idIdx < 0) return out;
  var companyIdx = colIndex_(headers, ["company_name", "company name"]);
  var nameIdx = colIndex_(headers, ["name", "first_name", "first name"]);
  var surnameIdx = colIndex_(headers, ["surname", "last_name", "last name"]);
  for (var i = 1; i < rows.length; i++) {
    var cid = toPositiveId_(rows[i][idIdx]);
    if (cid === "") continue;
    var company = companyIdx >= 0 ? normalizeName_(rows[i][companyIdx]) : "";
    var first = nameIdx >= 0 ? cleanText_(rows[i][nameIdx]) : "";
    var last = surnameIdx >= 0 ? cleanText_(rows[i][surnameIdx]) : "";
    var full = normalizeName_((first + " " + last).trim());
    if (company) out[company] = cid;
    if (full) out[full] = cid;
  }
  return out;
}

function buildCityNameToIdMap_(ss) {
  var out = {};
  var sh = ss.getSheetByName(CFG.citiesSheetName);
  if (!sh) return out;
  var rows = sh.getDataRange().getValues();
  if (!rows || rows.length < 2) return out;
  return buildCityNameMap_(rows);
}

function lookupCustomerIdByName_(map, name) {
  var key = normalizeName_(name);
  return key ? (map[key] || "") : "";
}

function lookupCityIdByName_(map, cityName) {
  var key = normalizeName_(cityName);
  return key ? (map[key] || "") : "";
}

function normalizeName_(value) {
  return cleanText_(value).toLowerCase().replace(/\s+/g, " ");
}

function dedupeKey_(combined) {
  var c = normalizeName_(combined);
  return c ? ("person:" + c) : "";
}

function splitPersonName_(combined) {
  var s = cleanText_(combined);
  if (!s) return { name: "", surname: "" };
  var parts = s.split(/\s+/);
  if (parts.length === 1) return { name: parts[0], surname: "" };
  return { name: parts.slice(0, -1).join(" "), surname: parts[parts.length - 1] };
}

function buildCityNameMap_(rows) {
  var out = {};
  if (!rows || rows.length < 2) return out;
  var headers = rows[0];
  var idIdx = colIndex_(headers, ["id", "city_id", "city id"]);
  var nameIdx = colIndex_(headers, ["city_name", "city name", "name", "city"]);
  if (idIdx < 0 || nameIdx < 0) return out;
  for (var i = 1; i < rows.length; i++) {
    var id = toPositiveId_(rows[i][idIdx]);
    var nm = normalizeName_(rows[i][nameIdx]);
    if (id !== "" && nm) out[nm] = id;
  }
  return out;
}

function isPlaceholderName_(nameNorm) {
  return nameNorm === "n/a" || nameNorm === "na" || nameNorm === "none" || nameNorm === "-";
}

function isSuspiciousCustomerName_(name) {
  var n = cleanText_(name);
  if (!n) return true;
  // Require at least one alphabetical character to avoid numeric/date corruption.
  if (!/[a-z]/i.test(n)) return true;
  return false;
}

function isLikelyCorruptedCustomerRow_(row, idx) {
  var id = toPositiveId_(row[idx.id]);
  var custId = toPositiveId_(row[idx.custId]);
  var name = cleanText_(row[idx.name]);
  var surname = cleanText_(row[idx.surname]);
  var combined = cleanText_(row[idx.combined]);
  var company = idx.company >= 0 ? cleanText_(row[idx.company]) : "";

  if (id === "" || custId === "") return false;
  if (combined !== "") return false;
  if (company !== "" && normalizeName_(company) !== "private") return false;

  var nameHasLetters = /[a-z]/i.test(name);
  var surnameLooksTimestamp = /^\d{4}-\d{2}-\d{2}/.test(surname);
  var nameIsTinyNumeric = /^\d+$/.test(name) && String(name).length <= 3;

  return (!nameHasLetters && surnameLooksTimestamp) || nameIsTinyNumeric;
}

function applyTemplateFormulasToAppendedRows_(sheet, startRow, rowCount, templateFormulasR1C1, preserveCols) {
  if (!templateFormulasR1C1 || templateFormulasR1C1.length === 0 || rowCount <= 0) {
    return;
  }

  var preserveIndex = {};
  Object.keys(preserveCols || {}).forEach(function (k) {
    var idx = preserveCols[k];
    if (idx !== undefined && idx >= 0) {
      preserveIndex[idx] = true;
    }
  });

  for (var c = 0; c < templateFormulasR1C1.length; c++) {
    if (preserveIndex[c]) {
      continue;
    }
    var f = templateFormulasR1C1[c];
    if (!f) {
      continue;
    }
    var colRange = sheet.getRange(startRow, c + 1, rowCount, 1);
    var formulas = [];
    for (var r = 0; r < rowCount; r++) {
      formulas.push([f]);
    }
    colRange.setFormulasR1C1(formulas);
  }
}

function resolveTemplateFormulasR1C1_(sheet, startRow, colCount, preserveCols) {
  var resolved = new Array(colCount).fill("");
  var preserveIndex = {};
  Object.keys(preserveCols || {}).forEach(function (k) {
    var idx = preserveCols[k];
    if (idx !== undefined && idx >= 0) {
      preserveIndex[idx] = true;
    }
  });

  var scanRows = startRow - CFG.dataStartRow;
  if (scanRows <= 0) {
    return resolved;
  }

  var formulasGrid = sheet.getRange(CFG.dataStartRow, 1, scanRows, colCount).getFormulasR1C1();
  for (var c = 0; c < colCount; c++) {
    if (preserveIndex[c]) {
      continue;
    }
    for (var r = formulasGrid.length - 1; r >= 0; r--) {
      if (formulasGrid[r][c]) {
        resolved[c] = formulasGrid[r][c];
        break;
      }
    }
  }
  return resolved;
}

function repairMissingCustomerFormulasCore_(sheet, cuIdx) {
  var lastRow = sheet.getLastRow();
  if (lastRow < CFG.dataStartRow) {
    return 0;
  }

  var colCount = sheet.getLastColumn();
  var preserveCols = {
    id: cuIdx.id,
    custId: cuIdx.custId,
    combined: cuIdx.combined
  };
  var templateFormulas = resolveTemplateFormulasR1C1_(sheet, lastRow + 1, colCount, preserveCols);

  var repaired = 0;
  var preserveIndex = {};
  Object.keys(preserveCols).forEach(function (k) {
    var idx = preserveCols[k];
    if (idx !== undefined && idx >= 0) {
      preserveIndex[idx] = true;
    }
  });

  var rowCount = lastRow - CFG.dataStartRow + 1;
  for (var c = 0; c < colCount; c++) {
    if (preserveIndex[c] || !templateFormulas[c]) {
      continue;
    }
    var rng = sheet.getRange(CFG.dataStartRow, c + 1, rowCount, 1);
    var values = rng.getValues();
    var formulas = rng.getFormulasR1C1();
    for (var r = 0; r < rowCount; r++) {
      if (!formulas[r][0] && cleanText_(values[r][0]) === "") {
        sheet.getRange(CFG.dataStartRow + r, c + 1).setFormulaR1C1(templateFormulas[c]);
        repaired++;
      }
    }
  }
  return repaired;
}