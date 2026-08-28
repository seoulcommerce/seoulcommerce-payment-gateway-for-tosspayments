#!/usr/bin/env python3
"""
Compile PO files to MO files for WordPress plugin translations.
This script converts .po files to .mo files without requiring gettext.
"""

import struct
import array
import os
from pathlib import Path

def generate_mo(po_file, mo_file):
    """
    Convert a PO file to MO file format.
    Simple implementation for WordPress translations.
    """
    print(f"Compiling {po_file} to {mo_file}...")
    
    # Read PO file
    with open(po_file, 'r', encoding='utf-8') as f:
        po_content = f.read()
    
    # Parse PO file (simple parser)
    messages = {}
    current_msgid = None
    current_msgstr = None
    in_msgid = False
    in_msgstr = False
    
    for line in po_content.split('\n'):
        line = line.strip()
        
        # Skip comments and empty lines
        if not line or line.startswith('#'):
            continue
            
        # msgid
        if line.startswith('msgid "'):
            if current_msgid is not None and current_msgstr is not None:
                messages[current_msgid] = current_msgstr
            current_msgid = line[7:-1]  # Remove 'msgid "' and trailing '"'
            in_msgid = True
            in_msgstr = False
            continue
            
        # msgstr
        if line.startswith('msgstr "'):
            current_msgstr = line[8:-1]  # Remove 'msgstr "' and trailing '"'
            in_msgid = False
            in_msgstr = True
            continue
            
        # Continuation lines
        if line.startswith('"') and line.endswith('"'):
            text = line[1:-1]
            if in_msgid:
                current_msgid += text
            elif in_msgstr:
                current_msgstr += text
    
    # Don't forget the last message
    if current_msgid is not None and current_msgstr is not None:
        messages[current_msgid] = current_msgstr
    
    # Filter out empty translations
    messages = {k: v for k, v in messages.items() if k and v}
    
    print(f"Found {len(messages)} translations")
    
    if not messages:
        print("Warning: No translations found!")
        return False
    
    # Build MO file
    # MO file format: https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html
    
    keys = sorted(messages.keys())
    offsets = []
    ids = b''
    strs = b''
    
    for key in keys:
        msg_id = key.encode('utf-8')
        msg_str = messages[key].encode('utf-8')
        
        offsets.append((len(ids), len(msg_id), len(strs), len(msg_str)))
        ids += msg_id + b'\x00'
        strs += msg_str + b'\x00'
    
    # Header
    keystart = 7 * 4 + 16 * len(keys)
    valuestart = keystart + len(ids)
    
    # MO file magic number
    output = struct.pack('Iiiiiii',
                        0x950412de,        # Magic number
                        0,                 # Version
                        len(keys),         # Number of entries
                        7 * 4,             # Start of key index
                        7 * 4 + 8 * len(keys),  # Start of value index
                        0,                 # Size of hash table
                        0)                 # Offset of hash table
    
    # Key index
    for offset in offsets:
        output += struct.pack('ii', offset[1], keystart + offset[0])
    
    # Value index
    for offset in offsets:
        output += struct.pack('ii', offset[3], valuestart + offset[2])
    
    # Keys and values
    output += ids + strs
    
    # Write MO file
    with open(mo_file, 'wb') as f:
        f.write(output)
    
    print(f"✓ Successfully created {mo_file}")
    return True

def main():
    """Main function to compile all PO files in languages directory."""
    script_dir = Path(__file__).parent
    languages_dir = script_dir / 'languages'
    
    if not languages_dir.exists():
        print(f"Error: {languages_dir} directory not found!")
        return
    
    # Find all PO files
    po_files = list(languages_dir.glob('*.po'))
    
    if not po_files:
        print(f"No .po files found in {languages_dir}")
        return
    
    print(f"Found {len(po_files)} PO file(s)\n")
    
    success_count = 0
    for po_file in po_files:
        mo_file = po_file.with_suffix('.mo')
        try:
            if generate_mo(str(po_file), str(mo_file)):
                success_count += 1
            print()
        except Exception as e:
            print(f"✗ Error compiling {po_file}: {e}\n")
    
    print(f"\n{'='*60}")
    print(f"Compilation complete: {success_count}/{len(po_files)} files compiled successfully")
    print(f"{'='*60}\n")
    
    if success_count > 0:
        print("✓ Translation files are ready to use!")
        print("\nTo test:")
        print("1. Set WordPress language to the translated language")
        print("2. Go to plugin settings")
        print("3. Verify translations appear correctly")
    else:
        print("✗ No files were compiled successfully")
        print("\nTroubleshooting:")
        print("- Check that PO files are not empty")
        print("- Verify PO file format is correct")
        print("- Try using Poedit or Loco Translate plugin instead")

if __name__ == '__main__':
    main()

