# 🔍 Model Explainability & Interpretability System

**Purpose**: Make model decisions transparent and debuggable for production monitoring  
**Integration**: Grafana dashboards, Langfuse traces, field test analysis  
**Last Updated**: December 17, 2025

---

## 🎯 Explainability Goals

1. **Why did the model generate this specific story?** → Attention visualization
2. **What training examples influenced this output?** → Retrieval provenance
3. **Is the model confident in this generation?** → Uncertainty quantification
4. **Which model features correlate with user ratings?** → Feature importance
5. **How can we debug poor quality outputs?** → Error analysis dashboard

---

## 🧠 Model Explainability Components

### 1. Attention Visualization

**What it shows**: Which input tokens the model focused on during generation

```python
# File: backend/model_explainability.py

import torch
import numpy as np
from typing import List, Dict, Tuple
import matplotlib.pyplot as plt
import seaborn as sns
from transformers import AutoModelForCausalLM, AutoTokenizer

class AttentionAnalyzer:
    """Analyze and visualize model attention patterns"""
    
    def __init__(self, model, tokenizer):
        self.model = model
        self.tokenizer = tokenizer
    
    def get_attention_weights(
        self,
        prompt: str,
        max_new_tokens: int = 256
    ) -> Tuple[List[str], np.ndarray]:
        """
        Generate text and extract attention weights
        
        Returns:
            tokens: List of generated tokens
            attention: Attention weights array (layers, heads, seq_len, seq_len)
        """
        
        # Encode prompt
        inputs = self.tokenizer(prompt, return_tensors="pt").to(self.model.device)
        
        # Generate with attention output
        with torch.no_grad():
            outputs = self.model.generate(
                **inputs,
                max_new_tokens=max_new_tokens,
                output_attentions=True,
                return_dict_in_generate=True
            )
        
        # Extract tokens
        generated_ids = outputs.sequences[0]
        tokens = [self.tokenizer.decode([token_id]) for token_id in generated_ids]
        
        # Extract attention (average across layers and heads)
        # Shape: (num_layers, num_heads, seq_len, seq_len)
        attention_weights = []
        
        for layer_attn in outputs.attentions:
            # layer_attn is tuple of attention for each generation step
            if isinstance(layer_attn, tuple):
                # Average across generation steps
                layer_avg = torch.stack([a for a in layer_attn]).mean(dim=0)
            else:
                layer_avg = layer_attn
            
            # Average across heads: (batch, heads, seq, seq) -> (seq, seq)
            head_avg = layer_avg.mean(dim=1).squeeze(0)
            attention_weights.append(head_avg.cpu().numpy())
        
        # Average across layers
        attention_matrix = np.mean(attention_weights, axis=0)
        
        return tokens, attention_matrix
    
    def visualize_attention(
        self,
        tokens: List[str],
        attention: np.ndarray,
        save_path: str = "attention_heatmap.png"
    ):
        """Create attention heatmap visualization"""
        
        plt.figure(figsize=(12, 10))
        
        sns.heatmap(
            attention,
            xticklabels=tokens[:30],  # Limit for readability
            yticklabels=tokens[:30],
            cmap="YlOrRd",
            cbar=True,
            square=True
        )
        
        plt.title("Model Attention Weights", fontsize=16)
        plt.xlabel("Input Tokens", fontsize=12)
        plt.ylabel("Output Tokens", fontsize=12)
        plt.xticks(rotation=45, ha='right')
        plt.yticks(rotation=0)
        plt.tight_layout()
        
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        print(f"✅ Attention heatmap saved: {save_path}")
        
        return save_path
    
    def get_top_attended_tokens(
        self,
        tokens: List[str],
        attention: np.ndarray,
        output_position: int,
        top_k: int = 10
    ) -> List[Tuple[str, float]]:
        """
        Get tokens that had highest attention for a specific output position
        
        Args:
            tokens: All tokens
            attention: Attention matrix
            output_position: Which output token to analyze
            top_k: How many top tokens to return
        
        Returns:
            List of (token, attention_weight) tuples
        """
        
        if output_position >= len(attention):
            output_position = len(attention) - 1
        
        # Get attention weights for this output position
        position_attention = attention[output_position]
        
        # Get top K indices
        top_indices = np.argsort(position_attention)[-top_k:][::-1]
        
        top_tokens = [
            (tokens[idx] if idx < len(tokens) else "<unk>", position_attention[idx])
            for idx in top_indices
        ]
        
        return top_tokens

# Example usage
def explain_generation(model, tokenizer, prompt: str):
    """Complete explanation of a single generation"""
    
    analyzer = AttentionAnalyzer(model, tokenizer)
    
    print(f"📝 Analyzing prompt: {prompt}")
    
    # Get attention
    tokens, attention = analyzer.get_attention_weights(prompt, max_new_tokens=100)
    
    # Visualize
    heatmap_path = analyzer.visualize_attention(tokens, attention)
    
    # Explain specific output tokens
    print("\n🔍 Top attended tokens for key outputs:")
    
    # Analyze first generated token
    print("\nFirst generated token:")
    top_attended = analyzer.get_top_attended_tokens(tokens, attention, output_position=len(prompt.split()), top_k=5)
    for token, weight in top_attended:
        print(f"  {token}: {weight:.3f}")
    
    # Analyze middle token
    mid_pos = len(tokens) // 2
    print(f"\nMiddle generated token (pos {mid_pos}):")
    top_attended = analyzer.get_top_attended_tokens(tokens, attention, output_position=mid_pos, top_k=5)
    for token, weight in top_attended:
        print(f"  {token}: {weight:.3f}")
    
    return {
        "tokens": tokens,
        "attention_heatmap": heatmap_path,
        "prompt_length": len(prompt.split()),
        "generated_length": len(tokens) - len(prompt.split())
    }
```

---

### 2. Retrieval Provenance Tracker

**What it shows**: Which ChromaDB stories influenced the generation

```python
# File: backend/retrieval_explainer.py

import chromadb
from typing import List, Dict
import json

class RetrievalProvenanceTracker:
    """Track which retrieved stories influenced model outputs"""
    
    def __init__(self, chroma_client, collection_name: str = "jeju_stories"):
        self.client = chroma_client
        self.collection = self.client.get_collection(name=collection_name)
    
    def explain_retrieval(
        self,
        query: str,
        n_results: int = 5
    ) -> Dict:
        """
        Explain which stories were retrieved and why
        
        Returns:
            {
                "query": str,
                "retrieved_stories": [
                    {
                        "title": str,
                        "similarity": float,
                        "metadata": dict,
                        "excerpt": str,
                        "why_relevant": str
                    }
                ],
                "semantic_dna": dict
            }
        """
        
        # Retrieve stories
        results = self.collection.query(
            query_texts=[query],
            n_results=n_results,
            include=["documents", "metadatas", "distances"]
        )
        
        retrieved_stories = []
        
        for i in range(len(results['ids'][0])):
            story_id = results['ids'][0][i]
            distance = results['distances'][0][i]
            similarity = 1 - distance  # Convert distance to similarity
            
            metadata = results['metadatas'][0][i]
            document = results['documents'][0][i]
            
            # Extract key features
            emotion = metadata.get('emotion_keyword', 'unknown')
            conflict = metadata.get('conflict_type', 'unknown')
            location = metadata.get('admin_region', 'unknown')
            
            # Explain why relevant
            why_relevant = self._explain_relevance(query, metadata, document)
            
            retrieved_stories.append({
                "story_id": story_id,
                "title": metadata.get('title', 'Untitled'),
                "similarity": similarity,
                "metadata": {
                    "emotion": emotion,
                    "conflict": conflict,
                    "location": location,
                    "category": metadata.get('category', 'unknown')
                },
                "excerpt": document[:200] + "..." if len(document) > 200 else document,
                "why_relevant": why_relevant
            })
        
        # Extract semantic DNA
        semantic_dna = self._extract_semantic_dna(retrieved_stories)
        
        return {
            "query": query,
            "retrieved_stories": retrieved_stories,
            "semantic_dna": semantic_dna,
            "num_retrieved": len(retrieved_stories)
        }
    
    def _explain_relevance(self, query: str, metadata: dict, document: str) -> str:
        """Generate human-readable explanation of why story is relevant"""
        
        reasons = []
        
        # Check emotion match
        emotion = metadata.get('emotion_keyword', '')
        if emotion in query:
            reasons.append(f"matches requested emotion '{emotion}'")
        
        # Check location match
        location = metadata.get('admin_region', '')
        if location in query:
            reasons.append(f"from requested location '{location}'")
        
        # Check keyword overlap
        query_keywords = set(query.lower().split())
        doc_keywords = set(document.lower().split())
        overlap = query_keywords.intersection(doc_keywords)
        
        if len(overlap) > 3:
            reasons.append(f"shares {len(overlap)} keywords with query")
        
        # Check category
        category = metadata.get('category', '')
        if category:
            reasons.append(f"belongs to '{category}' category")
        
        if not reasons:
            reasons.append("semantic similarity to query")
        
        return "; ".join(reasons)
    
    def _extract_semantic_dna(self, stories: List[Dict]) -> Dict:
        """Extract semantic DNA from retrieved stories"""
        
        # Count emotion keywords
        emotions = {}
        conflicts = {}
        locations = {}
        
        for story in stories:
            meta = story["metadata"]
            
            emotion = meta.get("emotion", "unknown")
            emotions[emotion] = emotions.get(emotion, 0) + 1
            
            conflict = meta.get("conflict", "unknown")
            conflicts[conflict] = conflicts.get(conflict, 0) + 1
            
            location = meta.get("location", "unknown")
            locations[location] = locations.get(location, 0) + 1
        
        return {
            "dominant_emotion": max(emotions, key=emotions.get) if emotions else "unknown",
            "emotion_distribution": emotions,
            "dominant_conflict": max(conflicts, key=conflicts.get) if conflicts else "unknown",
            "conflict_distribution": conflicts,
            "dominant_location": max(locations, key=locations.get) if locations else "unknown",
            "location_distribution": locations
        }
    
    def visualize_retrieval(self, explanation: Dict, save_path: str = "retrieval_explanation.html"):
        """Create HTML visualization of retrieval process"""
        
        html = f"""
<!DOCTYPE html>
<html>
<head>
    <title>Retrieval Explanation</title>
    <style>
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }}
        .query-box {{
            background: #2196F3;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }}
        .story-card {{
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }}
        .similarity-bar {{
            background: #4CAF50;
            height: 20px;
            border-radius: 5px;
            margin: 10px 0;
        }}
        .metadata {{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }}
        .tag {{
            background: #e0e0e0;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }}
        .dna-box {{
            background: #FFF3E0;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }}
    </style>
</head>
<body>
    <h1>🔍 Retrieval Explanation</h1>
    
    <div class="query-box">
        <h2>Query</h2>
        <p>{explanation['query']}</p>
        <small>Retrieved {explanation['num_retrieved']} stories</small>
    </div>
    
    <h2>Retrieved Stories (by relevance)</h2>
"""
        
        for i, story in enumerate(explanation['retrieved_stories'], 1):
            similarity_percent = story['similarity'] * 100
            
            html += f"""
    <div class="story-card">
        <h3>#{i}: {story['title']}</h3>
        
        <div class="similarity-bar" style="width: {similarity_percent}%"></div>
        <small>Similarity: {similarity_percent:.1f}%</small>
        
        <div class="metadata">
            <span class="tag">📍 {story['metadata']['location']}</span>
            <span class="tag">😊 {story['metadata']['emotion']}</span>
            <span class="tag">⚔️ {story['metadata']['conflict']}</span>
            <span class="tag">📚 {story['metadata']['category']}</span>
        </div>
        
        <p style="margin-top: 15px; font-style: italic; color: #666;">
            {story['excerpt']}
        </p>
        
        <div style="background: #E8F5E9; padding: 10px; border-radius: 5px; margin-top: 10px;">
            <strong>Why relevant:</strong> {story['why_relevant']}
        </div>
    </div>
"""
        
        # Add semantic DNA
        dna = explanation['semantic_dna']
        html += f"""
    <div class="dna-box">
        <h2>🧬 Semantic DNA</h2>
        <p><strong>Dominant Emotion:</strong> {dna['dominant_emotion']}</p>
        <p><strong>Dominant Conflict:</strong> {dna['dominant_conflict']}</p>
        <p><strong>Dominant Location:</strong> {dna['dominant_location']}</p>
        
        <h3>Distributions</h3>
        <pre>{json.dumps(dna, indent=2, ensure_ascii=False)}</pre>
    </div>
</body>
</html>
"""
        
        with open(save_path, "w", encoding="utf-8") as f:
            f.write(html)
        
        print(f"✅ Retrieval explanation saved: {save_path}")
        return save_path
```

---

### 3. Uncertainty Quantification

**What it shows**: How confident the model is in its generation

```python
# File: backend/uncertainty_estimator.py

import torch
import numpy as np
from scipy.stats import entropy

class UncertaintyEstimator:
    """Estimate model uncertainty in generations"""
    
    def __init__(self, model, tokenizer):
        self.model = model
        self.tokenizer = tokenizer
    
    def estimate_uncertainty(
        self,
        prompt: str,
        max_new_tokens: int = 256,
        num_samples: int = 5
    ) -> Dict:
        """
        Estimate uncertainty using multiple sampling runs
        
        Args:
            prompt: Input prompt
            max_new_tokens: Max generation length
            num_samples: Number of samples to generate
        
        Returns:
            {
                "mean_entropy": float,  # Average token-level entropy
                "variance": float,      # Generation variance
                "confidence": float,    # Overall confidence (0-1)
                "samples": List[str]    # Generated samples
            }
        """
        
        inputs = self.tokenizer(prompt, return_tensors="pt").to(self.model.device)
        
        samples = []
        token_entropies = []
        
        self.model.eval()
        
        with torch.no_grad():
            for _ in range(num_samples):
                # Generate with temperature sampling
                outputs = self.model.generate(
                    **inputs,
                    max_new_tokens=max_new_tokens,
                    do_sample=True,
                    temperature=0.7,
                    top_p=0.9,
                    output_scores=True,
                    return_dict_in_generate=True
                )
                
                # Decode sample
                generated_text = self.tokenizer.decode(outputs.sequences[0], skip_special_tokens=True)
                samples.append(generated_text)
                
                # Calculate token-level entropy
                if outputs.scores:
                    for score in outputs.scores:
                        # score shape: (batch, vocab_size)
                        probs = torch.softmax(score, dim=-1)
                        token_entropy = entropy(probs.cpu().numpy()[0])
                        token_entropies.append(token_entropy)
        
        # Calculate statistics
        mean_entropy = np.mean(token_entropies) if token_entropies else 0
        
        # Calculate generation variance (how different are the samples?)
        variance = self._calculate_sample_variance(samples)
        
        # Overall confidence (inverse of uncertainty)
        # Low entropy + low variance = high confidence
        confidence = 1 / (1 + mean_entropy + variance)
        
        return {
            "mean_entropy": mean_entropy,
            "variance": variance,
            "confidence": confidence,
            "samples": samples,
            "num_samples": num_samples,
            "interpretation": self._interpret_uncertainty(mean_entropy, variance, confidence)
        }
    
    def _calculate_sample_variance(self, samples: List[str]) -> float:
        """Calculate variance between generated samples"""
        
        if len(samples) < 2:
            return 0.0
        
        # Simple metric: average edit distance between samples
        from difflib import SequenceMatcher
        
        similarities = []
        
        for i in range(len(samples)):
            for j in range(i + 1, len(samples)):
                similarity = SequenceMatcher(None, samples[i], samples[j]).ratio()
                similarities.append(similarity)
        
        # Variance is inverse of similarity
        avg_similarity = np.mean(similarities) if similarities else 1.0
        variance = 1 - avg_similarity
        
        return variance
    
    def _interpret_uncertainty(self, entropy: float, variance: float, confidence: float) -> str:
        """Human-readable interpretation of uncertainty"""
        
        if confidence > 0.8:
            return "High confidence: Model is very certain about this generation"
        elif confidence > 0.6:
            return "Medium confidence: Model has some uncertainty"
        elif confidence > 0.4:
            return "Low confidence: Model is uncertain, multiple valid outputs possible"
        else:
            return "Very low confidence: Model is highly uncertain, output may be unreliable"

# Example usage
def analyze_generation_confidence(model, tokenizer, prompt: str):
    """Complete uncertainty analysis"""
    
    estimator = UncertaintyEstimator(model, tokenizer)
    
    print(f"🎲 Estimating uncertainty for: {prompt}")
    
    results = estimator.estimate_uncertainty(prompt, max_new_tokens=100, num_samples=5)
    
    print(f"\n📊 Uncertainty Metrics:")
    print(f"  Mean Entropy: {results['mean_entropy']:.3f}")
    print(f"  Variance: {results['variance']:.3f}")
    print(f"  Confidence: {results['confidence']:.3f}")
    print(f"\n💡 {results['interpretation']}")
    
    print(f"\n📝 Generated Samples:")
    for i, sample in enumerate(results['samples'], 1):
        print(f"\n  Sample {i}:")
        print(f"  {sample[:200]}...")
    
    return results
```

---

### 4. Feature Importance Analyzer

**What it shows**: Which input features correlate with model quality/user ratings

```python
# File: backend/feature_importance.py

import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.inspection import permutation_importance
import matplotlib.pyplot as plt

class FeatureImportanceAnalyzer:
    """Analyze which features predict model quality"""
    
    def __init__(self):
        self.model = RandomForestRegressor(n_estimators=100, random_state=42)
    
    def analyze_features(
        self,
        data: List[Dict]
    ) -> Dict:
        """
        Analyze feature importance for predicting user ratings
        
        Args:
            data: List of generation records with features and ratings
                [
                    {
                        "features": {
                            "prompt_length": int,
                            "num_retrieved": int,
                            "similarity_avg": float,
                            "emotion": str,
                            "location": str,
                            "model_tier": str,
                            "latency_ms": float
                        },
                        "user_rating": float,
                        "bleu_score": float
                    }
                ]
        
        Returns:
            {
                "feature_importances": Dict[str, float],
                "top_features": List[Tuple[str, float]],
                "visualization_path": str
            }
        """
        
        # Convert to DataFrame
        df = pd.DataFrame([
            {
                **record['features'],
                'user_rating': record.get('user_rating', 0),
                'bleu_score': record.get('bleu_score', 0)
            }
            for record in data
        ])
        
        # Encode categorical features
        df_encoded = pd.get_dummies(df, columns=['emotion', 'location', 'model_tier'])
        
        # Separate features and target
        X = df_encoded.drop(['user_rating', 'bleu_score'], axis=1)
        y = df_encoded['user_rating']
        
        # Train model
        self.model.fit(X, y)
        
        # Get feature importances
        importances = self.model.feature_importances_
        feature_names = X.columns
        
        # Create importance dict
        importance_dict = {
            name: importance
            for name, importance in zip(feature_names, importances)
        }
        
        # Sort by importance
        top_features = sorted(
            importance_dict.items(),
            key=lambda x: x[1],
            reverse=True
        )[:10]
        
        # Visualize
        viz_path = self._visualize_importance(top_features)
        
        return {
            "feature_importances": importance_dict,
            "top_features": top_features,
            "visualization_path": viz_path,
            "model_r2_score": self.model.score(X, y)
        }
    
    def _visualize_importance(self, top_features: List[Tuple[str, float]]) -> str:
        """Create bar chart of feature importances"""
        
        names, values = zip(*top_features)
        
        plt.figure(figsize=(10, 6))
        plt.barh(range(len(names)), values)
        plt.yticks(range(len(names)), names)
        plt.xlabel('Importance')
        plt.title('Top 10 Features Predicting User Rating')
        plt.tight_layout()
        
        save_path = "feature_importance.png"
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        
        print(f"✅ Feature importance chart saved: {save_path}")
        return save_path

# Example usage
def analyze_field_test_features(field_test_data: List[Dict]):
    """Analyze which features led to higher user ratings"""
    
    analyzer = FeatureImportanceAnalyzer()
    
    results = analyzer.analyze_features(field_test_data)
    
    print("\n🔍 Feature Importance Analysis")
    print(f"  Model R² Score: {results['model_r2_score']:.3f}")
    print("\n  Top 5 Features:")
    
    for name, importance in results['top_features'][:5]:
        print(f"    {name}: {importance:.3f}")
    
    return results
```

---

## 📊 Grafana Dashboard Integration

### Explainability Metrics Panel

```json
{
  "title": "Model Explainability Dashboard",
  "panels": [
    {
      "id": 1,
      "title": "Confidence Distribution",
      "type": "histogram",
      "targets": [
        {
          "expr": "histogram_quantile(0.5, jeju_model_confidence_bucket)",
          "legendFormat": "Median Confidence"
        }
      ]
    },
    {
      "id": 2,
      "title": "Attention Heatmap (Latest)",
      "type": "image",
      "url": "http://localhost:8000/api/latest-attention-heatmap"
    },
    {
      "id": 3,
      "title": "Retrieved Stories per Request",
      "type": "graph",
      "targets": [
        {
          "expr": "avg(jeju_retrieved_stories_count)",
          "legendFormat": "Avg Retrieved"
        }
      ]
    },
    {
      "id": 4,
      "title": "Feature Importance (Live)",
      "type": "table",
      "targets": [
        {
          "expr": "topk(10, jeju_feature_importance)",
          "legendFormat": "{{feature}}"
        }
      ]
    },
    {
      "id": 5,
      "title": "Uncertainty vs User Rating",
      "type": "scatter",
      "targets": [
        {
          "expr": "jeju_model_entropy",
          "legendFormat": "Entropy"
        },
        {
          "expr": "jeju_user_rating",
          "legendFormat": "User Rating"
        }
      ]
    }
  ]
}
```

---

## 🔬 Complete Explanation Pipeline

```python
# File: backend/complete_explainer.py

from model_explainability import AttentionAnalyzer, explain_generation
from retrieval_explainer import RetrievalProvenanceTracker
from uncertainty_estimator import UncertaintyEstimator, analyze_generation_confidence
import json

class CompleteExplainer:
    """
    Unified interface for complete model explainability
    Integrates all explanation components
    """
    
    def __init__(self, model, tokenizer, chroma_client):
        self.attention_analyzer = AttentionAnalyzer(model, tokenizer)
        self.retrieval_tracker = RetrievalProvenanceTracker(chroma_client)
        self.uncertainty_estimator = UncertaintyEstimator(model, tokenizer)
        self.model = model
        self.tokenizer = tokenizer
    
    def explain_complete(
        self,
        user_query: str,
        generated_story: str,
        trace_id: str
    ) -> Dict:
        """
        Generate complete explanation for a story generation
        
        Returns comprehensive report including:
        - Retrieval provenance (what was retrieved and why)
        - Attention patterns (what model focused on)
        - Uncertainty (how confident model was)
        - Feature analysis (what contributed to quality)
        """
        
        print(f"🔍 Generating complete explanation for trace: {trace_id}")
        
        # 1. Retrieval explanation
        print("  1/3 Analyzing retrieval...")
        retrieval_explanation = self.retrieval_tracker.explain_retrieval(user_query, n_results=5)
        retrieval_html = self.retrieval_tracker.visualize_retrieval(
            retrieval_explanation,
            save_path=f"explanations/{trace_id}_retrieval.html"
        )
        
        # 2. Attention explanation
        print("  2/3 Analyzing attention...")
        tokens, attention = self.attention_analyzer.get_attention_weights(user_query, max_new_tokens=100)
        attention_heatmap = self.attention_analyzer.visualize_attention(
            tokens,
            attention,
            save_path=f"explanations/{trace_id}_attention.png"
        )
        
        # 3. Uncertainty explanation
        print("  3/3 Estimating uncertainty...")
        uncertainty = self.uncertainty_estimator.estimate_uncertainty(user_query, max_new_tokens=100, num_samples=3)
        
        # Combine all explanations
        complete_explanation = {
            "trace_id": trace_id,
            "user_query": user_query,
            "generated_story": generated_story,
            
            "retrieval": {
                "num_retrieved": retrieval_explanation['num_retrieved'],
                "top_story": retrieval_explanation['retrieved_stories'][0]['title'] if retrieval_explanation['retrieved_stories'] else None,
                "semantic_dna": retrieval_explanation['semantic_dna'],
                "explanation_html": retrieval_html
            },
            
            "attention": {
                "num_tokens": len(tokens),
                "heatmap_path": attention_heatmap,
                "top_attended": self.attention_analyzer.get_top_attended_tokens(
                    tokens, attention, output_position=len(tokens)//2, top_k=5
                )
            },
            
            "uncertainty": {
                "confidence": uncertainty['confidence'],
                "mean_entropy": uncertainty['mean_entropy'],
                "variance": uncertainty['variance'],
                "interpretation": uncertainty['interpretation']
            },
            
            "summary": self._generate_summary(retrieval_explanation, uncertainty)
        }
        
        # Save complete explanation
        with open(f"explanations/{trace_id}_complete.json", "w", encoding="utf-8") as f:
            json.dump(complete_explanation, f, indent=2, ensure_ascii=False)
        
        print(f"✅ Complete explanation saved: explanations/{trace_id}_complete.json")
        
        return complete_explanation
    
    def _generate_summary(self, retrieval_exp: Dict, uncertainty: Dict) -> str:
        """Generate human-readable summary"""
        
        dna = retrieval_exp['semantic_dna']
        
        summary = f"""
🔍 Generation Explanation Summary

📚 Retrieval:
  - Retrieved {retrieval_exp['num_retrieved']} stories
  - Dominant emotion: {dna['dominant_emotion']}
  - Dominant conflict: {dna['dominant_conflict']}
  - Dominant location: {dna['dominant_location']}

🎯 Confidence:
  - Confidence level: {uncertainty['confidence']:.1%}
  - {uncertainty['interpretation']}

💡 Key Insights:
  - Model drew inspiration from {retrieval_exp['num_retrieved']} Jeju folklore stories
  - Generation is {'highly confident' if uncertainty['confidence'] > 0.7 else 'moderately confident' if uncertainty['confidence'] > 0.5 else 'uncertain'}
  - Dominant theme: {dna['dominant_emotion']} with {dna['dominant_conflict']} conflict
"""
        
        return summary.strip()

# API endpoint
from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI()

class ExplanationRequest(BaseModel):
    trace_id: str
    user_query: str
    generated_story: str

@app.post("/api/explain")
async def get_explanation(request: ExplanationRequest):
    """Get complete explanation for a generation"""
    
    # Initialize explainer (in practice, this would be a singleton)
    explainer = CompleteExplainer(model, tokenizer, chroma_client)
    
    explanation = explainer.explain_complete(
        user_query=request.user_query,
        generated_story=request.generated_story,
        trace_id=request.trace_id
    )
    
    return explanation

@app.get("/api/latest-attention-heatmap")
async def get_latest_attention():
    """Serve latest attention heatmap for Grafana"""
    from fastapi.responses import FileResponse
    import os
    
    # Get most recent heatmap
    explanation_dir = "explanations/"
    files = [f for f in os.listdir(explanation_dir) if f.endswith("_attention.png")]
    
    if not files:
        return {"error": "No heatmaps available"}
    
    latest_file = max([os.path.join(explanation_dir, f) for f in files], key=os.path.getctime)
    
    return FileResponse(latest_file, media_type="image/png")
```

---

## 🎯 Usage in Production

### During Field Test

```python
# After each story generation, create explanation

from complete_explainer import CompleteExplainer

# Generate story
story_response = await api.generate_story(gps_location)

# Create explanation
explainer = CompleteExplainer(model, tokenizer, chroma_client)

explanation = explainer.explain_complete(
    user_query=story_response['user_query'],
    generated_story=story_response['story'],
    trace_id=story_response['trace_id']
)

# Log to Langfuse
langfuse.log(
    trace_id=story_response['trace_id'],
    metadata={
        "confidence": explanation['uncertainty']['confidence'],
        "retrieval_count": explanation['retrieval']['num_retrieved'],
        "semantic_dna": explanation['retrieval']['semantic_dna']
    }
)

# If low confidence, flag for review
if explanation['uncertainty']['confidence'] < 0.5:
    print(f"⚠️ Low confidence generation: {story_response['trace_id']}")
    # Send alert to monitoring dashboard
```

---

## ✅ Next Steps

1. **Implement attention visualization** → See what model focuses on
2. **Set up retrieval tracking** → Understand ChromaDB influence
3. **Add uncertainty metrics to Grafana** → Monitor confidence live
4. **Create explainability API endpoints** → Serve to dashboards
5. **Generate explanations for field test** → Debug poor outputs

Which component would you like me to implement first?
