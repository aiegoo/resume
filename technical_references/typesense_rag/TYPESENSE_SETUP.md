# Typesense Search Integration - Complete Setup Guide

## Overview

This setup enables full-text search of the "Asana Pranayama Mudra Bandha" book with **clickable page images** in search results.

## What's Been Created

### 1. Typesense Import Script
**File:** [import_to_typesense.py](import_to_typesense.py)

Imports the OCR database into Typesense with:
- Full text content (73,060 words)
- Keywords (2,004 unique)
- Both PDF and book page numbers
- **Image file paths** for each page
- Metadata (word counts, page types)

### 2. Search Interface
**File:** [search_interface.html](search_interface.html)

Beautiful web interface with:
- Real-time search as you type
- Highlighted search matches
- Keyword tags
- **Click to view page images** in modal popup
- Book page numbers + PDF page references

### 3. Web Server Script
**File:** [start_web_server.sh](start_web_server.sh)

Simple Python HTTP server to serve the search interface and images.

## Setup Instructions

### Step 1: Install Typesense

Using Docker (recommended):
```bash
docker run -d \
  -p 8108:8108 \
  -v/tmp/typesense-data:/data \
  typesense/typesense:latest \
  --data-dir /data \
  --api-key=xyz \
  --enable-cors
```

Or download binary from: https://typesense.org/docs/guide/install-typesense.html

### Step 2: Import Data into Typesense

```bash
cd /Users/a2485/repos/aiegoo/yoga
python3 import_to_typesense.py
```

This will:
- ✅ Create Typesense collection schema
- ✅ Import 467 pages with content
- ✅ Include image paths for each page
- ✅ Run test searches to verify

Expected output:
```
✓ Connected to Typesense
✓ Created collection: asana_pranayama_mudra_bandha
✓ Prepared 467 documents
✓ Import complete: 467 documents
```

### Step 3: Start Web Server

```bash
cd /Users/a2485/repos/aiegoo/yoga
./start_web_server.sh
```

This serves:
- Search interface at http://localhost:3000/search_interface.html
- Page images at http://localhost:3000/references/asana-pranayama-mudra-and-bandha/images/

### Step 4: Open Search Interface

Open in your browser:
```
http://localhost:3000/search_interface.html
```

## How It Works

### Search Flow

1. **User types search query** → "pranayama breathing"
2. **Typesense searches** text and keywords fields
3. **Results display** with highlighted matches
4. **Click "View Page Image"** button
5. **Modal popup shows** the actual page image from the book
6. **Image path** comes from the `image_path` field in Typesense

### Data Structure in Typesense

Each document contains:
```json
{
  "pdf_page": 58,
  "book_page": 41,
  "text": "NAUKASANA Exercise 21 - (i) : Naukasana...",
  "keywords": ["naukasana", "asana", "boat", "pose"],
  "word_count": 245,
  "has_content": true,
  "page_type": "content",
  "image_path": "/Users/a2485/repos/.../images/page_0058.png",
  "image_url": "/images/page_0058.png",
  "text_file_path": "/text/page_0058.txt"
}
```

### Image Display

When a search result is clicked:
1. JavaScript reads the `image_path` from the document
2. Converts absolute path to relative URL
3. Opens modal with the page image
4. Image loaded from: `http://localhost:3000/references/asana-pranayama-mudra-and-bandha/images/page_XXXX.png`

## Customization

### Change Typesense Configuration

Edit `import_to_typesense.py`:
```python
importer = YogaBookTypesenseImporter(
    host='localhost',      # Change to your host
    port='8108',           # Change to your port
    api_key='your-api-key' # Use your API key
)
```

### Change Web Server Port

Edit `start_web_server.sh`:
```bash
PORT=3000  # Change to desired port
```

### Customize Search Interface

Edit `search_interface.html`:
- **Colors:** Modify CSS gradient colors
- **Search fields:** Change `query_by` parameter
- **Results per page:** Modify `per_page` value
- **Image display:** Customize modal styling

## API Usage Examples

### Search via cURL

```bash
curl 'http://localhost:8108/collections/asana_pranayama_mudra_bandha/documents/search' \
  -H 'X-TYPESENSE-API-KEY: xyz' \
  -d 'q=pranayama&query_by=text,keywords&per_page=5'
```

### Search via Python

```python
import typesense

client = typesense.Client({
    'nodes': [{'host': 'localhost', 'port': '8108', 'protocol': 'http'}],
    'api_key': 'xyz'
})

results = client.collections['asana_pranayama_mudra_bandha'].documents.search({
    'q': 'meditation',
    'query_by': 'text,keywords',
    'per_page': 10
})

for hit in results['hits']:
    doc = hit['document']
    print(f"Book page {doc['book_page']}: {doc['image_path']}")
```

### Search via JavaScript

```javascript
const client = new Typesense.Client({
    'nodes': [{'host': 'localhost', 'port': '8108', 'protocol': 'http'}],
    'apiKey': 'xyz'
});

const results = await client.collections('asana_pranayama_mudra_bandha')
    .documents()
    .search({
        'q': 'ujjayi',
        'query_by': 'text,keywords'
    });

results.hits.forEach(hit => {
    console.log('Page:', hit.document.book_page);
    console.log('Image:', hit.document.image_path);
});
```

## Advanced Features

### Filter by Page Type

Search only content pages:
```javascript
{
    'q': 'pranayama',
    'query_by': 'text,keywords',
    'filter_by': 'page_type:=content'
}
```

### Filter by Keyword

Find pages tagged with specific keyword:
```javascript
{
    'q': 'breathing',
    'query_by': 'text,keywords',
    'filter_by': 'keywords:=[pranayama]'
}
```

### Range Search by Page

Find results in specific page range:
```javascript
{
    'q': 'asana',
    'query_by': 'text,keywords',
    'filter_by': 'book_page:>100 && book_page:<200'
}
```

## Integration with Your Application

### For Web Apps

1. Copy `search_interface.html` as a template
2. Modify the `showPageImage()` function to match your URL structure
3. Update Typesense host/port if deployed remotely
4. Add authentication if needed

### For Backend APIs

Create an API endpoint that:
1. Receives search query
2. Queries Typesense
3. Returns results with image URLs
4. Serves images via CDN or static file server

Example (Express.js):
```javascript
app.get('/api/search', async (req, res) => {
    const results = await typesenseClient.collections('...')
        .documents()
        .search({q: req.query.q, query_by: 'text,keywords'});
    
    res.json(results);
});

app.use('/images', express.static('references/asana-pranayama-mudra-and-bandha/images'));
```

### For Mobile Apps

1. Query Typesense from your mobile app
2. Download and cache page images
3. Display images in native image viewer
4. Use book page numbers for citations

## Troubleshooting

### Typesense Not Running
```bash
# Check if Typesense is running
curl http://localhost:8108/health

# Start Typesense with Docker
docker start <container-id>
```

### Import Fails
```bash
# Check Typesense logs
docker logs <container-id>

# Verify database file exists
ls -la references/asana-pranayama-mudra-and-bandha/json/ocr_database.json
```

### Images Not Loading
```bash
# Check web server is running
lsof -i :3000

# Verify image files exist
ls -la references/asana-pranayama-mudra-and-bandha/images/ | head
```

### CORS Errors
Add `--enable-cors` flag when starting Typesense, or configure CORS in your web server.

## Production Deployment

For production use:

1. **Deploy Typesense** on a server (not localhost)
2. **Use HTTPS** for secure connections
3. **Add authentication** to protect your API key
4. **Use CDN** for serving images
5. **Enable caching** for better performance
6. **Monitor** Typesense metrics and logs

## Performance Notes

- **Search Speed:** Sub-100ms for most queries
- **Index Size:** ~50MB for 467 pages
- **Images:** 486 PNG files (~200MB total)
- **Memory:** Typesense uses ~100MB RAM

## Next Steps

1. ✅ Test search functionality
2. ✅ Try example queries (pranayama, meditation, headache, etc.)
3. ✅ Click results to view page images
4. ✅ Customize the interface to match your needs
5. ✅ Integrate with your yoga practice app

Enjoy searching the wisdom of Swami Satyananda Saraswati! 🧘
