#!/usr/bin/env python3
"""
Generate SQL UPDATE statements to fix waybill city_id values
Matches customer name + city from Excel to update waybills
"""
import pandas as pd
import json
import re

# Load city mapping
with open('assets/city_mapping.json', 'r') as f:
    city_map_raw = json.load(f)
    city_map = {}
    for k, v in city_map_raw.items():
        if isinstance(v, dict):
            city_map[k.lower()] = v['city_id']

# Load Excel
df = pd.read_excel('waybill_excel/Waybills_31-10-2025.xlsx', sheet_name='waybills')
df = df.replace({pd.NA: None, pd.NaT: None})

# Create mapping: customer_name -> city_id
customer_city = {}
for _, row in df.iterrows():
    customer = str(row.get('Customer', '')).strip()
    city = str(row.get('City', '')).strip()
    
    if customer and city:
        city_lower = city.lower()
        city_id = city_map.get(city_lower, 9)
        customer_city[customer] = city_id

print(f"Found {len(customer_city)} unique customers with cities\n")

# Generate SQL UPDATE statements
# We'll match by customer name (first_name + surname)
sql_statements = []
sql_statements.append("-- Fix waybill city_id values from Excel city names")
sql_statements.append("-- Matches customer name to determine correct city_id")
sql_statements.append("SET FOREIGN_KEY_CHECKS=0;\n")

# For each customer, generate UPDATE statement
for customer_name, city_id in sorted(customer_city.items()):
    # Split customer name (format: "First Last" or "Company Name")
    name_parts = customer_name.split(' ', 1)
    
    if len(name_parts) >= 2:
        first_name = name_parts[0].strip()
        surname = name_parts[1].strip()
        
        # Generate UPDATE using customer name match
        first_escaped = first_name.replace("'", "''")
        surname_escaped = surname.replace("'", "''")
        sql_statements.append(
            f"UPDATE wp_kit_waybills w "
            f"INNER JOIN wp_kit_customers c ON w.customer_id = c.cust_id "
            f"SET w.city_id = {city_id} "
            f"WHERE w.city_id = 9 "
            f"AND c.name = '{first_escaped}' "
            f"AND c.surname = '{surname_escaped}';"
        )
    else:
        # Company name or single name
        customer_escaped = customer_name.replace("'", "''")
        sql_statements.append(
            f"UPDATE wp_kit_waybills w "
            f"INNER JOIN wp_kit_customers c ON w.customer_id = c.cust_id "
            f"SET w.city_id = {city_id} "
            f"WHERE w.city_id = 9 "
            f"AND (c.name LIKE '%{customer_escaped}%' OR c.company_name LIKE '%{customer_escaped}%');"
        )

sql_statements.append("\nSET FOREIGN_KEY_CHECKS=1;")

# Write to file
output_file = 'assets/fix_all_waybill_cities.sql'
with open(output_file, 'w', encoding='utf-8') as f:
    f.write('\n'.join(sql_statements))

print(f"✓ Generated SQL file: {output_file}")
print(f"  {len(customer_city)} UPDATE statements created")
print("\nRun this SQL file in your database to fix waybill city_id values")

