import pandas as pd
from datetime import datetime

def find_duplicate_emails_in_csv(input_csv, email_column='E-post', output_file='duplicate-email-rows.txt'):
    try:
        # Read the CSV file
        df = pd.read_csv(input_csv)
        
        print(f"Successfully read the CSV file. Shape: {df.shape}")
        #print("Columns in the DataFrame:")
        #for col in df.columns:
        #    print(f"- '{col}'")
        
        if email_column not in df.columns:
            print(f"Error: Column '{email_column}' not found in the CSV file.")
            return
        
        # Remove rows with empty emails
        df = df[df[email_column].notna() & (df[email_column] != '')]

        # Find duplicate emails
        duplicates = df[df.duplicated(subset=[email_column], keep=False)]
        
        if duplicates.empty:
            print("No duplicate emails found.")
            return
        
        # Sort duplicates by email for easier reading
        duplicates = duplicates.sort_values(by=email_column)
        
        # Write duplicates to file
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(f"Duplicate emails found on {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}:\n\n")
            f.write(duplicates.to_string(index=False))
        
        print(f"Found {len(duplicates)} rows with duplicate emails.")
        print(f"Duplicate email rows written to {output_file}")

    except Exception as e:
        print(f"An error occurred: {str(e)}")

# Usage example
input_csv = 'medlemmar-cleaned.csv'  # This should be the output from your Excel cleaning script
find_duplicate_emails_in_csv(input_csv)