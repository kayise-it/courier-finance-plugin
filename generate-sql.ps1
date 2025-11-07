$csvPath = "C:\MAMP\htdocs\08600\wp-content\plugins\courier-finance-plugin\waybill_excel\08600 Waybills - Waybills.csv"
$data = Import-Csv $csvPath | Where-Object { $_.'NewWB' -and $_.'NewWB'.Trim() -ne '' }

$invariant = [System.Globalization.CultureInfo]::InvariantCulture

function ParseDecimal([string]$value) {
    if ([string]::IsNullOrWhiteSpace($value)) { return 0.0 }
    $clean = [regex]::Replace($value, "[^0-9\.-]", "")
    if ([string]::IsNullOrWhiteSpace($clean)) { return 0.0 }
    return [double]::Parse($clean, $invariant)
}

function ParseInt([string]$value) {
    if ([string]::IsNullOrWhiteSpace($value)) { return 0 }
    $clean = [regex]::Replace($value, "[^0-9-]", "")
    if ([string]::IsNullOrWhiteSpace($clean)) { return 0 }
    return [int]$clean
}

function ParseDate([string]$value) {
    if ([string]::IsNullOrWhiteSpace($value)) { return $null }
    $value = $value.Trim()
    $formats = @('yyyy/MM/dd','yyyy-MM-dd','dd/MM/yyyy','MM/dd/yyyy','dd/MM/yy','MM/dd/yy','d/M/yyyy','d/M/yy')
    $res = [datetime]::MinValue
    if ([datetime]::TryParseExact($value, $formats, $invariant, [System.Globalization.DateTimeStyles]::None, [ref]$res)) { return $res }
    if ([datetime]::TryParse($value, [ref]$res)) { return $res }
    return $null
}

function SqlEscape([string]$value) {
    if ($null -eq $value) { return "" }
    return $value.Replace("'", "''")
}

function Num([double]$value) {
    return $value.ToString('0.########', $invariant)
}

function ConvertToPhpSerialized($value) {
    if ($null -eq $value) { return "N;" }

    if ($value -is [bool]) {
        if ($value) { return "b:1;" } else { return "b:0;" }
    }

    if ($value -is [int] -or $value -is [long]) {
        return ("i:{0};" -f $value)
    }

    if ($value -is [double] -or $value -is [single] -or $value -is [decimal]) {
        $formatted = [System.Convert]::ToString([double]$value, [System.Globalization.CultureInfo]::InvariantCulture)
        return ("d:{0};" -f $formatted)
    }

    if ($value -is [string]) {
        $encoding = [System.Text.Encoding]::UTF8
        $length = $encoding.GetByteCount($value)
        $escaped = $value.Replace('\', '\\').Replace('"', '\"')
        return ('s:{0}:"{1}";' -f $length, $escaped)
    }

    if ($value -is [System.Collections.IDictionary]) {
        $dict = $value
        $sb = New-Object System.Text.StringBuilder
        $sb.Append("a:" + $dict.Count + ":{") | Out-Null
        foreach ($key in $dict.Keys) {
            $sb.Append((ConvertToPhpSerialized([string]$key))) | Out-Null
            $sb.Append((ConvertToPhpSerialized($dict[$key]))) | Out-Null
        }
        $sb.Append("}") | Out-Null
        return $sb.ToString()
    }

    if ($value -is [System.Collections.IEnumerable] -and -not ($value -is [string])) {
        $list = @()
        foreach ($item in $value) { $list += ,$item }
        $sb = New-Object System.Text.StringBuilder
        $sb.Append("a:" + $list.Count + ":{") | Out-Null
        for ($i = 0; $i -lt $list.Count; $i++) {
            $sb.Append("i:" + $i + ";") | Out-Null
            $sb.Append((ConvertToPhpSerialized($list[$i]))) | Out-Null
        }
        $sb.Append("}") | Out-Null
        return $sb.ToString()
    }

    return ConvertToPhpSerialized([string]$value.ToString())
}

function GetUniqueInvoiceNumber([string]$value, [int]$waybillNo, $registry) {
    if ([string]::IsNullOrWhiteSpace($value)) { return $null }
    $base = $value.Trim()
    if ($base.Length -eq 0) { return $null }
    $candidate = $base
    $suffix = 1
    while ($registry.Contains($candidate.ToLowerInvariant())) {
        if ($suffix -eq 1) {
            $candidate = "$base-$waybillNo"
        } else {
            $candidate = "$base-$waybillNo-$suffix"
        }
        $suffix++
    }
    $registry.Add($candidate.ToLowerInvariant()) | Out-Null
    return $candidate
}

function GetCountryForCity([string]$value) {
    $normalized = ''
    if (-not [string]::IsNullOrWhiteSpace($value)) {
        $normalized = $value.Trim().ToLowerInvariant()
    }
    switch ($normalized) {
        'dar es salaam' { return 'Tanzania' }
        'arusha' { return 'Tanzania' }
        'iringa' { return 'Tanzania' }
        'zanzibar' { return 'Tanzania' }
        'mafinga' { return 'Tanzania' }
        'makambako' { return 'Tanzania' }
        'moshi' { return 'Tanzania' }
        'sumbawanga' { return 'Tanzania' }
        'kilombero' { return 'Tanzania' }
        'dodoma' { return 'Tanzania' }
        'mbeya' { return 'Tanzania' }
        'ngorongoro' { return 'Tanzania' }
        '' { return 'Tanzania' }
        '0' { return 'Tanzania' }
        default { return 'Tanzania' }
    }
}

function GetCountryMeta([string]$name) {
    switch ($name) {
        'South Africa' { return @{ Code = 'ZA'; ChargeGroup = 1; IsActive = 1 } }
        'Tanzania' { return @{ Code = 'TZ'; ChargeGroup = 2; IsActive = 1 } }
        default {
            $code = if ([string]::IsNullOrWhiteSpace($name)) { 'XX' } else {
                ($name.Trim() -replace '[^A-Za-z]', '').Substring(0, [Math]::Min(2, ($name.Trim() -replace '[^A-Za-z]', '').Length)).ToUpperInvariant()
            }
            return @{ Code = $code; ChargeGroup = 0; IsActive = 1 }
        }
    }
}

function EnsureCountry([string]$name, [string]$code, [int]$chargeGroup, [int]$isActive, [hashtable]$map, [System.Collections.Generic.List[pscustomobject]]$list, [ref]$nextId) {
    if ($map.ContainsKey($name)) { return $map[$name] }
    $nextId.Value++
    $newId = $nextId.Value
    $map[$name] = $newId
    $list.Add([PSCustomObject]@{
        Id = $newId
        Name = $name
        Code = $code
        ChargeGroup = $chargeGroup
        IsActive = $isActive
    })
    return $newId
}

function EnsureDirection([string]$origin, [string]$destination, [string]$description, [hashtable]$map, [System.Collections.Generic.List[pscustomobject]]$list, [ref]$nextId) {
    $key = "$origin|$destination"
    if ($map.ContainsKey($key)) { return $map[$key] }
    $nextId.Value++
    $newId = $nextId.Value
    $map[$key] = $newId
    $list.Add([PSCustomObject]@{
        Id = $newId
        Origin = $origin
        Destination = $destination
        Description = $description
        IsActive = 1
    })
    return $newId
}

$cityMap = @{}
$cityCountryMap = @{}
$customerMap = @{}
$driverMap = @{}
$cityList = @()
$customerList = @()
$driverList = @()
$deliveryList = @()
$waybillList = @()
$itemList = @()
$countryMap = @{}
$countryList = New-Object 'System.Collections.Generic.List[pscustomobject]'
$directionMap = @{}
$directionList = New-Object 'System.Collections.Generic.List[pscustomobject]'
$deliveryMap = @{}
$invoiceRegistry = New-Object 'System.Collections.Generic.HashSet[string]'

$cityIdSeed = 2000
$customerIdSeed = 5000
$driverIdSeed = 300
$deliveryIdSeed = 6000
$countryIdSeed = 0
$directionIdSeed = 0

$DEFAULT_ORIGIN_COUNTRY_ID = 1
$DEFAULT_SADC_CHARGE = 1000.0
$DEFAULT_SAD500_CHARGE = 5000.0
$DEFAULT_USD_PRICE = 100.0
$DEFAULT_USD_ZAR_RATE = 18.50
$DEFAULT_INTERNATIONAL_PRICE = $DEFAULT_USD_PRICE * $DEFAULT_USD_ZAR_RATE
$VAT_RATE = 0.10

# Seed baseline countries and directions aligning with plugin defaults
$southAfricaId = EnsureCountry 'South Africa' 'ZA' 1 1 $countryMap $countryList ([ref]$countryIdSeed)
$tanzaniaId = EnsureCountry 'Tanzania' 'TZ' 2 1 $countryMap $countryList ([ref]$countryIdSeed)
$warehousedDirectionId = EnsureDirection 'South Africa' 'South Africa' 'Warehoused items' $directionMap $directionList ([ref]$directionIdSeed)
$saToTzDirectionId = EnsureDirection 'South Africa' 'Tanzania' 'South Africa to Tanzania' $directionMap $directionList ([ref]$directionIdSeed)
$tzToSaDirectionId = EnsureDirection 'Tanzania' 'South Africa' 'Tanzania to South Africa' $directionMap $directionList ([ref]$directionIdSeed)

$groups = $data | Group-Object { ParseInt($_.'NewWB') } | Sort-Object Name

foreach ($group in $groups) {
    $rows = $group.Group
    $first = $rows[0]
    $waybillNo = ParseInt($first.'NewWB')

    $cityName = if ([string]::IsNullOrWhiteSpace($first.City)) { 'Unknown City' } else { $first.City }
    $destinationCountryName = GetCountryForCity($cityName)
    if (-not $countryMap.ContainsKey($destinationCountryName)) {
        $meta = GetCountryMeta($destinationCountryName)
        EnsureCountry $destinationCountryName $meta.Code $meta.ChargeGroup $meta.IsActive $countryMap $countryList ([ref]$countryIdSeed) | Out-Null
    }
    $destinationCountryId = $countryMap[$destinationCountryName]

    if (-not $cityMap.ContainsKey($cityName)) {
        $cityIdSeed++
        $cityMap[$cityName] = $cityIdSeed
        $cityCountryMap[$cityName] = $destinationCountryId
        $cityList += [PSCustomObject]@{ Id = $cityIdSeed; Name = $cityName; CountryId = $destinationCountryId }
    } else {
        if ($cityCountryMap.ContainsKey($cityName)) {
            $destinationCountryId = $cityCountryMap[$cityName]
        } else {
            $cityCountryMap[$cityName] = $destinationCountryId
        }
    }
    $cityId = $cityMap[$cityName]
    $cityCountryMap[$cityName] = $destinationCountryId

    $originCountryName = 'South Africa'
    if (-not $countryMap.ContainsKey($originCountryName)) {
        $metaOrigin = GetCountryMeta($originCountryName)
        EnsureCountry $originCountryName $metaOrigin.Code $metaOrigin.ChargeGroup $metaOrigin.IsActive $countryMap $countryList ([ref]$countryIdSeed) | Out-Null
    }
    $originCountryId = $countryMap[$originCountryName]

    $directionKey = "$originCountryName|$destinationCountryName"
    if (-not $directionMap.ContainsKey($directionKey)) {
        EnsureDirection $originCountryName $destinationCountryName "$originCountryName to $destinationCountryName" $directionMap $directionList ([ref]$directionIdSeed) | Out-Null
    }
    $directionId = $directionMap[$directionKey]

    $driverName = if ([string]::IsNullOrWhiteSpace($first.Driver)) { 'Unassigned' } else { $first.Driver.Trim() }
    if (-not $driverMap.ContainsKey($driverName)) {
        $driverIdSeed++
        $driverMap[$driverName] = $driverIdSeed
        $driverList += [PSCustomObject]@{ Id = $driverIdSeed; Name = $driverName }
    }

    $companyKey = if ([string]::IsNullOrWhiteSpace($first.Company)) { '' } else { $first.Company }
    $cellKey = if ([string]::IsNullOrWhiteSpace($first.Cell)) { '' } else { $first.Cell }
    $customerNameKey = if ([string]::IsNullOrWhiteSpace($first.Customer)) { 'unknown' } else { $first.Customer }
    $customerKey = ($customerNameKey + '|' + $companyKey + '|' + $cellKey).ToLowerInvariant()

    if (-not $customerMap.ContainsKey($customerKey)) {
        $customerIdSeed++
        $customerMap[$customerKey] = $customerIdSeed
        $nameParts = ($customerNameKey -split '\s+', 2)
        $custName = if ($nameParts.Length -gt 0 -and -not [string]::IsNullOrWhiteSpace($nameParts[0])) { $nameParts[0] } else { 'Customer' }
        $custSurname = if ($nameParts.Length -gt 1) { $nameParts[1] } else { if ([string]::IsNullOrWhiteSpace($companyKey)) { 'Imported' } else { $companyKey } }
        $customerList += [PSCustomObject]@{
            Id = $customerIdSeed
            Name = $custName
            Surname = $custSurname
            Cell = $first.Cell
            Email = $first.Email
            Company = $companyKey
            Address = $first.Address
            CityId = $cityId
            CountryId = $destinationCountryId
        }
    }
    $customerId = $customerMap[$customerKey]

    $dispatchDate = ParseDate($first.'Dispatch Date')
    $dispatchKey = if ($dispatchDate) { $dispatchDate.ToString('yyyy-MM-dd') } else { ([string]($first.'Dispatch Date')).Trim() }
    $deliveryKey = "$driverName|$dispatchKey|$directionId"
    if (-not $deliveryMap.ContainsKey($deliveryKey)) {
        $deliveryIdSeed++
        $deliveryReference = "DEL-$deliveryIdSeed"
        $deliveryMap[$deliveryKey] = [PSCustomObject]@{
            Id = $deliveryIdSeed
            Reference = $deliveryReference
            DirectionId = $directionId
            CityId = $cityId
            DispatchDateObject = $dispatchDate
            DispatchDateRaw = $dispatchKey
            DriverName = $driverName
        }
        $deliveryList += $deliveryMap[$deliveryKey]
    }
    $deliveryId = $deliveryMap[$deliveryKey].Id

    $goodsDate = ParseDate($first.'Goods Received')

    $massCharge = ParseDecimal($first.'MASS COST')
    $volCharge = ParseDecimal($first.'VOL COST')
    $length = ParseDecimal($first.LENGTH)
    $width = ParseDecimal($first.WIDTH)
    $height = ParseDecimal($first.HEIGHT)
    $massKg = ParseDecimal($first.'T MASS')
    $volumeTotal = ParseDecimal($first.'T VOLUME')
    $chargeBasis = if (($first.BASIS).ToUpperInvariant() -eq 'VOLUME') { 'volume' } else { 'mass' }
    $primaryCharge = if ($chargeBasis -eq 'volume') { $volCharge } else { $massCharge }
    $massRateUsed = if ($massKg -gt 0) { [math]::Round($massCharge / $massKg, 6) } else { 0 }
    $volumeRateUsed = if ($volumeTotal -gt 0) { [math]::Round($volCharge / $volumeTotal, 6) } else { 0 }

    $uniqueInvoiceNumber = GetUniqueInvoiceNumber $first.'CL INV #' $waybillNo $invoiceRegistry

    $itemsTotal = 0.0
    foreach ($row in $rows) { $itemsTotal += ParseDecimal($row.'CLIENT INVOICE ( R)') }

    $vatInclude = if (($first.VAT).ToUpperInvariant() -eq 'TRUE') { 1 } else { 0 }
    $includeSad500 = if (($first.SAD500).ToUpperInvariant() -eq 'TRUE') { 1 } else { 0 }
    $includeSadc = if (($first.SADC).ToUpperInvariant() -eq 'TRUE') { 1 } else { 0 }

    $vatTotal = 0.0
    $additionalCharges = 0.0
    if ($includeSad500 -eq 1) { $additionalCharges += $DEFAULT_SAD500_CHARGE }
    if ($includeSadc -eq 1) { $additionalCharges += $DEFAULT_SADC_CHARGE }
    if ($vatInclude -eq 1) {
        $vatTotal = [math]::Round($itemsTotal * $VAT_RATE, 2)
        $additionalCharges += $vatTotal
    } else {
        $additionalCharges += $DEFAULT_INTERNATIONAL_PRICE
    }

    $waybillTotal = [math]::Round($primaryCharge + $additionalCharges, 2)

    $othersOrdered = [ordered]@{
        waybill_description = ($first.'Waybill description')
        mass_rate = $massRateUsed
        total_volume = $volumeTotal
        manny = 0
        manny_mass_rate = 0
        manny_volume_rate = 0
        destination_city_id = $cityId
        destination_country_id = $destinationCountryId
        origin_city_id = 0
        origin_country_id = $DEFAULT_ORIGIN_COUNTRY_ID
        used_charge_basis = $chargeBasis
        use_custom_volume_rate = 0
        custom_volume_rate_per_m3 = 0
        volume_rate_used = $volumeRateUsed
    }

    if ($vatInclude -eq 1) {
        $othersOrdered['vat_total'] = $vatTotal
    } else {
        $othersOrdered['usd_to_zar_rate_used'] = $DEFAULT_USD_ZAR_RATE
        $othersOrdered['international_price_rands'] = [math]::Round($DEFAULT_INTERNATIONAL_PRICE, 2)
        if ($includeSad500 -eq 1) { $othersOrdered['include_sad500'] = $DEFAULT_SAD500_CHARGE }
        if ($includeSadc -eq 1) { $othersOrdered['include_sadc'] = $DEFAULT_SADC_CHARGE }
    }

    $miscData = [ordered]@{
        misc_items = @()
        misc_total = 0
        others = $othersOrdered
    }
    $miscSerialized = ConvertToPhpSerialized($miscData)

    $waybillList += [PSCustomObject]@{
        WaybillNo = $waybillNo
        Description = $first.'Waybill description'
        DirectionId = $directionId
        CityId = $cityId
        DeliveryId = $deliveryId
        CustomerId = $customerId
        WaybillNumber = ParseInt($first.'WAYBILL #')
        InvoiceNumber = $uniqueInvoiceNumber
        ProductInvoiceAmount = $waybillTotal
        ItemsTotal = $itemsTotal
        Length = $length
        Width = $width
        Height = $height
        MassKg = $massKg
        Volume = $volumeTotal
        MassCharge = $massCharge
        VolumeCharge = $volCharge
        ChargeBasis = $chargeBasis
        VatInclude = $vatInclude
        IncludeSAD500 = $includeSad500
        IncludeSADC = $includeSadc
        GoodsDate = $goodsDate
        MiscSerialized = $miscSerialized
    }

    foreach ($row in $rows) {
        $qty = ParseInt($row.Qty)
        if ($qty -le 0) { $qty = 1 }
        $price = ParseDecimal($row.'CLIENT INVOICE ( R)')
        $totalMass = ParseDecimal($row.'T MASS')
        $totalVolume = ParseDecimal($row.'T VOLUME')
        $itemList += [PSCustomObject]@{
            WaybillNo = $waybillNo
            ItemName = $row.'Item description'
            Quantity = $qty
            UnitPrice = if ($qty -gt 0) { [math]::Round($price / $qty, 2) } else { $price }
            UnitMass = if ($qty -gt 0) { $totalMass / $qty } else { $totalMass }
            UnitVolume = if ($qty -gt 0) { $totalVolume / $qty } else { $totalVolume }
            TotalPrice = $price
            ClientInvoice = $row.'CL INV #'
        }
    }
}

$sb = New-Object System.Text.StringBuilder
$sb.AppendLine('-- Auto-generated from CSV ' + (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')) | Out-Null
$sb.AppendLine('START TRANSACTION;') | Out-Null
$sb.AppendLine('SET FOREIGN_KEY_CHECKS = 0;') | Out-Null

if ($countryList.Count -gt 0) {
    $countryValues = $countryList | ForEach-Object {
        "(" + $_.Id + ", '" + (SqlEscape $_.Name) + "', '" + (SqlEscape $_.Code) + "', " + $_.ChargeGroup + ", " + $_.IsActive + ", NOW())"
    }
    $sb.AppendLine('INSERT IGNORE INTO `wp_kit_operating_countries` (`id`, `country_name`, `country_code`, `charge_group`, `is_active`, `created_at`) VALUES ' + ($countryValues -join ",`n") + ';') | Out-Null
}

if ($directionList.Count -gt 0) {
    $directionValues = $directionList | ForEach-Object {
        $originId = $countryMap[$_.Origin]
        $destinationId = $countryMap[$_.Destination]
        "(" + $_.Id + ", " + $originId + ", " + $destinationId + ", '" + (SqlEscape $_.Description) + "', " + $_.IsActive + ", NOW())"
    }
    $sb.AppendLine('INSERT IGNORE INTO `wp_kit_shipping_directions` (`id`, `origin_country_id`, `destination_country_id`, `description`, `is_active`, `created_at`) VALUES ' + ($directionValues -join ",`n") + ';') | Out-Null
}

if ($cityList.Count -gt 0) {
    $cityValues = $cityList | ForEach-Object { "(" + $_.Id + ", " + $_.CountryId + ", '" + (SqlEscape $_.Name) + "', 1, NOW())" }
    $sb.AppendLine('INSERT IGNORE INTO `wp_kit_operating_cities` (`id`, `country_id`, `city_name`, `is_active`, `created_at`) VALUES ' + ($cityValues -join ",`n") + ';') | Out-Null
}

if ($driverList.Count -gt 0) {
    $driverValues = $driverList | ForEach-Object { "(" + $_.Id + ", '" + (SqlEscape $_.Name) + "', NULL, NULL, NULL, 1, NOW(), NOW())" }
    $sb.AppendLine('INSERT IGNORE INTO `wp_kit_drivers` (`id`, `name`, `phone`, `email`, `license_number`, `is_active`, `created_at`, `updated_at`) VALUES ' + ($driverValues -join ",`n") + ';') | Out-Null
}

if ($customerList.Count -gt 0) {
    $custValues = $customerList | ForEach-Object {
        $cell = if ($_.Cell) { "'" + (SqlEscape $_.Cell) + "'" } else { 'NULL' }
        $email = if ($_.Email) { "'" + (SqlEscape $_.Email) + "'" } else { 'NULL' }
        $company = if ($_.Company) { "'" + (SqlEscape $_.Company) + "'" } else { 'NULL' }
        $address = if ($_.Address) { "'" + (SqlEscape $_.Address) + "'" } else { 'NULL' }
        "(" + $_.Id + ", '" + (SqlEscape $_.Name) + "', '" + (SqlEscape $_.Surname) + "', " + $cell + ", NULL, " + $email + ", " + $company + ", " + $address + ", " + $_.CityId + ", " + $_.CountryId + ", NOW())"
    }
    $sb.AppendLine('INSERT IGNORE INTO `wp_kit_customers` (`cust_id`, `name`, `surname`, `cell`, `telephone`, `email_address`, `company_name`, `address`, `city_id`, `country_id`, `created_at`) VALUES ' + ($custValues -join ",`n") + ';') | Out-Null
}

if ($deliveryList.Count -gt 0) {
    $delValues = $deliveryList | ForEach-Object {
        $date = if ($_.DispatchDateObject) {
            "'" + $_.DispatchDateObject.ToString('yyyy-MM-dd') + "'"
        } elseif (-not [string]::IsNullOrWhiteSpace($_.DispatchDateRaw)) {
            $parsedFallback = ParseDate($_.DispatchDateRaw)
            if ($parsedFallback) { "'" + $parsedFallback.ToString('yyyy-MM-dd') + "'" } else { 'NULL' }
        } else {
            'NULL'
        }
        "(" + $_.Id + ", '" + (SqlEscape $_.Reference) + "', " + $_.DirectionId + ", " + $_.CityId + ", " + $date + ", NULL, 'delivered', 1, NOW())"
    }
    $sb.AppendLine('INSERT IGNORE INTO `wp_kit_deliveries` (`id`, `delivery_reference`, `direction_id`, `destination_city_id`, `dispatch_date`, `truck_number`, `status`, `created_by`, `created_at`) VALUES ' + ($delValues -join ",`n") + ';') | Out-Null
}

$waybillNos = $waybillList | ForEach-Object { $_.WaybillNo } | Sort-Object -Unique
if ($waybillNos.Count -gt 0) {
    $sb.AppendLine('DELETE FROM `wp_kit_waybill_items` WHERE `waybillno` IN (' + ($waybillNos -join ',') + ');') | Out-Null
    $sb.AppendLine('DELETE FROM `wp_kit_waybills` WHERE `waybill_no` IN (' + ($waybillNos -join ',') + ');') | Out-Null
}

if ($waybillList.Count -gt 0) {
    $wbValues = @()
    foreach ($row in $waybillList) {
        $desc = SqlEscape $row.Description
        $invoiceNumber = if ([string]::IsNullOrWhiteSpace($row.InvoiceNumber)) { 'NULL' } else { "'" + (SqlEscape $row.InvoiceNumber) + "'" }
        $createdAt = if ($row.GoodsDate) { "'" + $row.GoodsDate.ToString('yyyy-MM-dd') + "'" } else { 'NOW()' }
        $miscSql = "'" + (SqlEscape $row.MiscSerialized) + "'"
        $wbValues += "('NewWB $($row.WaybillNo) - $desc', $($row.DirectionId), $($row.CityId), $($row.DeliveryId), $($row.CustomerId), 'pending', NULL, $($row.WaybillNo), $invoiceNumber, $(Num($row.ProductInvoiceAmount)), $(Num($row.ItemsTotal)), $(Num($row.Length)), $(Num($row.Width)), $(Num($row.Height)), $(Num($row.MassKg)), $(Num($row.Volume)), $(Num($row.MassCharge)), $(Num($row.VolumeCharge)), '$($row.ChargeBasis)', $($row.VatInclude), 0, $miscSql, $($row.IncludeSAD500), $($row.IncludeSADC), 0, 'TRK-$($row.WaybillNo)', NULL, 1, 1, 'pending', 0, $createdAt, $createdAt)"
    }
    $sb.AppendLine('INSERT INTO `wp_kit_waybills` (`description`, `direction_id`, `city_id`, `delivery_id`, `customer_id`, `approval`, `approval_userid`, `waybill_no`, `product_invoice_number`, `product_invoice_amount`, `waybill_items_total`, `item_length`, `item_width`, `item_height`, `total_mass_kg`, `total_volume`, `mass_charge`, `volume_charge`, `charge_basis`, `vat_include`, `warehouse`, `miscellaneous`, `include_sad500`, `include_sadc`, `return_load`, `tracking_number`, `qr_code_data`, `created_by`, `last_updated_by`, `status`, `status_userid`, `created_at`, `last_updated_at`) VALUES ' + ($wbValues -join ",`n") + ';') | Out-Null
}

if ($itemList.Count -gt 0) {
    $itemValues = @()
    foreach ($row in $itemList) {
        $name = SqlEscape $row.ItemName
        $clientInv = if ([string]::IsNullOrWhiteSpace($row.ClientInvoice)) { 'NULL' } else { "'" + (SqlEscape $row.ClientInvoice) + "'" }
        $itemValues += "($($row.WaybillNo), '$name', $($row.Quantity), $(Num($row.UnitPrice)), $(Num($row.UnitMass)), $(Num($row.UnitVolume)), $(Num($row.TotalPrice)), $clientInv, NOW())"
    }
    $sb.AppendLine('INSERT INTO `wp_kit_waybill_items` (`waybillno`, `item_name`, `quantity`, `unit_price`, `unit_mass`, `unit_volume`, `total_price`, `client_invoice`, `created_at`) VALUES ' + ($itemValues -join ",`n") + ';') | Out-Null
}

$sb.AppendLine('SET FOREIGN_KEY_CHECKS = 1;') | Out-Null
$sb.AppendLine('COMMIT;') | Out-Null

[System.IO.File]::WriteAllText("newSQL.sql", $sb.ToString(), (New-Object System.Text.UTF8Encoding($false)))
