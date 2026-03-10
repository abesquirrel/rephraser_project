#!/usr/bin/env python3
import os
import json
import csv
import argparse
import mysql.connector
import numpy as np
from datetime import datetime

def get_db_connection(host, user, password, database, port):
    return mysql.connector.connect(
        host=host,
        user=user,
        password=password,
        database=database,
        port=port
    )

def export_kb(args):
    print(f"Connecting to database {args.database} on {args.host}:{args.port}...")
    try:
        conn = get_db_connection(args.host, args.user, args.password, args.database, args.port)
        cursor = conn.cursor(dictionary=True)
        
        query = "SELECT id, original_text, rephrased_text, keywords, is_template, category, hits, created_at, embedding FROM knowledge_bases"
        cursor.execute(query)
        rows = cursor.fetchall()
        cursor.close()
        conn.close()
        
        print(f"Fetched {len(rows)} entries.")
        
        data = []
        for row in rows:
            item = {
                "id": row['id'],
                "original_text": row['original_text'],
                "rephrased_text": row['rephrased_text'],
                "keywords": row['keywords'],
                "is_template": bool(row['is_template']),
                "category": row['category'],
                "hits": row['hits'],
                "created_at": str(row['created_at']) if row['created_at'] else None
            }
            
            if args.include_embeddings and row['embedding']:
                emb_array = np.frombuffer(row['embedding'], dtype='float32')
                item['embedding'] = emb_array.tolist()
            
            data.append(item)
            
        if args.format == 'json':
            output_file = args.output or f"kb_export_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(data, f, indent=2, ensure_ascii=False)
            print(f"Exported to {output_file}")
            
        elif args.format == 'csv':
            output_file = args.output or f"kb_export_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
            if data:
                keys = data[0].keys()
                with open(output_file, 'w', newline='', encoding='utf-8') as f:
                    dict_writer = csv.DictWriter(f, keys)
                    dict_writer.writeheader()
                    dict_writer.writerows(data)
                print(f"Exported to {output_file}")
            else:
                print("No data to export.")
                
    except Exception as e:
        print(f"Error during export: {e}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Export Knowledge Base from MariaDB")
    parser.add_argument("--host", default="localhost", help="Database host (default: localhost)")
    parser.add_argument("--port", type=int, default=3306, help="Database port (default: 3306)")
    parser.add_argument("--user", default="rephraser", help="Database user (default: rephraser)")
    parser.add_argument("--password", default="secret", help="Database password (default: secret)")
    parser.add_argument("--database", default="rephraser_db", help="Database name (default: rephraser_db)")
    parser.add_argument("--format", choices=["json", "csv"], default="json", help="Export format (default: json)")
    parser.add_argument("--output", help="Output file path")
    parser.add_argument("--include-embeddings", action="store_true", help="Include embeddings in the export")
    
    args = parser.parse_args()
    export_kb(args)
