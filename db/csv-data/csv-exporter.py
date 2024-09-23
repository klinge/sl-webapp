import pandas as pd
import re

def clean_cell(cell):
    if isinstance(cell, str):
        # Remove line breaks
        cell = cell.replace('\n', ' ').replace('\r', '')
        # Remove multiple spaces
        cell = re.sub(' +', ' ', cell)
        # Remove leading and trailing whitespace
        cell = cell.strip()
    return cell

def clean_and_export_excel(input_file, output_file, sheet_name=0):
    # Read the Excel file
    df = pd.read_excel(input_file, sheet_name=sheet_name)
    
    # Clean the data using apply instead of applymap
    df = df.apply(lambda col: col.apply(clean_cell))
    
    # Remove any completely empty rows
    df = df.dropna(how='all')
    
    # Remove any completely empty columns
    df = df.dropna(axis=1, how='all')
    
    # Export to CSV
    df.to_csv(output_file, index=False, encoding='utf-8')
    
    print(f"Cleaned data exported to {output_file}")


# Usage example
input_file = 'Adressregister-AJ-240921.xlsx'
output_file = 'medlemmar-cleaned.csv'
clean_and_export_excel(input_file, output_file)