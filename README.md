# Laravel RAG AI (Ollama + Qdrant)

Simple Retrieval-Augmented Generation (RAG) system built with Laravel, local LLM (Ollama), and Qdrant vector database.

---

## 🚀 Features

* Upload documents (PDF, TXT, MD)
* Text extraction (PDF + OCR fallback ready)
* Chunking & embeddings
* Vector search with Qdrant
* Multi-query retrieval
* AI answers with sources
* Local LLM (no OpenAI required)

---

## 🧱 Stack

* Laravel
* Ollama (LLM + embeddings)
* Qdrant (vector DB)
* Redis (queue)
* PostgreSQL (storage)
* Docker

---

## ⚙️ Requirements

* Docker + Docker Compose
* ~8GB RAM (recommended)

---

## 📦 Installation

### 1. Clone project

```bash
git clone https://github.com/ychekan/laravel-ai-agent-example.git
cd laravel-ai-agent-example
```

---

### 2. Copy env

```bash
cp .env.example .env
```

---

### 3. Set environment variables

```env
OLLAMA_BASE_URL=http://rag_ollama:11434
OLLAMA_EMBED_MODEL=nomic-embed-text
OLLAMA_CHAT_MODEL=qwen2:1.5b

QDRANT_BASE_URL=http://rag_qdrant:6333
QDRANT_COLLECTION=documents
```

---

### 4. Build containers

```bash
docker compose build
```

---

### 5. Start services

```bash
docker compose up -d
```

---

## 🤖 Install Ollama Models

Enter Ollama container:

```bash
docker compose exec rag_ollama bash
```

Install embedding model:

```bash
ollama pull nomic-embed-text
```

Install chat model:

```bash
ollama pull qwen2:1.5b
```

Check installed models:

```bash
ollama list
```

---

## 📦 Create Qdrant collection

Before using RAG, you need to create a collection in Qdrant.

🔹 Option 1 — via curl

```bash
curl -X PUT http://localhost:6333/collections/documents \
    -H "Content-Type: application/json" \
    -d '{
        "vectors": {
            "size": 768,
            "distance": "Cosine"
        }
    }'
```

---

## ⚠️ Important

* `nomic-embed-text` → vector size = **768**
* Collection MUST match embedding size

---

## 🔍 Qdrant

### Check collection

```bash
curl http://localhost:6333/collections
```

### Embedding test

```bash
curl http://localhost:11434/api/embeddings -d '{
  "model": "nomic-embed-text",
  "input": "test embedding"
}'
```

### Recreate collection (if needed)

```bash
curl -X DELETE http://localhost:6333/collections/documents
```

then create again

---

## 🤖 Test embedding manually

```bash
curl http://localhost:11434/api/embeddings -d '{
        "model": "nomic-embed-text",
        "input": "test embedding"
    }'
```

---

## 🧪 Quick checks

### Ollama

```bash
curl http://localhost:11434/api/tags
```

---

## 🗄️ Setup Laravel

### Install dependencies:

```bash
docker compose exec rag_app composer install
```

### Run migrations:

```bash
docker compose exec rag_app php artisan migrate
```

### Start queue worker:

```bash
docker compose exec rag_app php artisan queue:work
```

---

## 📄 Usage

### Upload document

* Send file (PDF, TXT, MD)
* System will:

    * parse text
    * split into chunks
    * generate embeddings
    * store in Qdrant

---

### Ask question

* System decides:

    * direct answer OR RAG
* Retrieves relevant chunks
* Generates answer with sources

---

## 🧠 Architecture

```
User Question
     ↓
Router (RAG or not)
     ↓
Query Expansion
     ↓
Retriever (Qdrant)
     ↓
Top-K Chunks
     ↓
LLM Answer (Ollama)
```

---

## 📂 Project Structure (important parts)

```
app/
  Services/
    ParserService.php
    ChunkService.php
    OllamaService.php
    QdrantService.php
    RouterService.php
    AnswerService.php
```

---

## ⚠️ Notes

* Some PDFs may require OCR (scan documents)
* Embeddings model must support `/api/embeddings`
* Do not use chat models for embeddings

---

## 🧪 Debug

Check Ollama:

```bash
curl http://localhost:11434/api/tags
```

Check Qdrant:

```bash
curl http://localhost:6333/collections
```

---

## 🚀 Future Improvements

* Auto-create collection after composer install (Laravel command)
* Healthcheck container for Ollama and Qdrant
* OCR pipeline (Tesseract)
* Reranking
* Hybrid search (BM25 + vector)
* Streaming responses
* UI dashboard

---

## 🧑‍💻 Author

Built as AI pet-project for learning RAG systems.

---

## 📝 License
MIT License
