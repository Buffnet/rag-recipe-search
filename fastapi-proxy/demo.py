#!/usr/bin/env python3
"""
FastAPI Proxy Demo Script
Perfect for 60-second Loom video demonstration
"""

import asyncio
import httpx
import time
import json
from rich.console import Console
from rich.table import Table
from rich.progress import Progress, SpinnerColumn, TextColumn
from rich.panel import Panel

console = Console()

FASTAPI_URL = "http://localhost:8001"
LARAVEL_URL = "http://localhost:8080/api"

async def demo_performance_comparison():
    """Compare FastAPI proxy vs Laravel direct"""
    console.print("\n🚀 [bold blue]Performance Comparison: FastAPI Proxy vs Laravel Direct[/bold blue]\n")
    
    search_data = {"ingredients": "chicken, tomato", "limit": 5}
    
    # Test Laravel direct
    start_time = time.time()
    async with httpx.AsyncClient() as client:
        response = await client.post(f"{LARAVEL_URL}/search/ingredients", json=search_data)
        laravel_results = response.json()
    laravel_time = (time.time() - start_time) * 1000
    
    # Test FastAPI proxy
    start_time = time.time()
    async with httpx.AsyncClient() as client:
        response = await client.post(f"{FASTAPI_URL}/search/ingredients", json=search_data)
        fastapi_results = response.json()
    fastapi_time = (time.time() - start_time) * 1000
    
    # Create comparison table
    table = Table(title="Performance Results")
    table.add_column("Endpoint", style="cyan", no_wrap=True)
    table.add_column("Response Time (ms)", style="magenta")
    table.add_column("Results Count", style="green")
    table.add_column("Features", style="yellow")
    
    table.add_row(
        "Laravel Direct", 
        f"{laravel_time:.1f}ms", 
        str(laravel_results['count']),
        "Basic response"
    )
    table.add_row(
        "FastAPI Proxy", 
        f"{fastapi_time:.1f}ms", 
        str(fastapi_results['count']),
        "Cached: " + str(fastapi_results.get('cached', False))
    )
    
    console.print(table)

async def demo_enhanced_search():
    """Demonstrate enhanced parallel search"""
    console.print("\n⚡ [bold green]Enhanced Parallel Search Demo[/bold green]\n")
    
    with Progress(
        SpinnerColumn(),
        TextColumn("[progress.description]{task.description}"),
        console=console,
    ) as progress:
        
        task = progress.add_task("Running parallel searches...", total=1)
        
        async with httpx.AsyncClient() as client:
            response = await client.post(f"{FASTAPI_URL}/search/enhanced", json={
                "query": "italian pasta",
                "limit": 5
            })
            results = response.json()
        
        progress.update(task, completed=1)
    
    # Display results
    console.print(f"✅ Query: [bold]{results['query']}[/bold]")
    console.print(f"📊 Successful searches: {results['successful_searches']}/3")
    console.print(f"⏱️  Response time: {results['response_time_ms']}ms")
    console.print(f"🔍 Results found: {results['count']}")
    
    # Show top results
    if results['results']:
        console.print("\n[bold]Top Results:[/bold]")
        for i, recipe in enumerate(results['results'][:3], 1):
            console.print(f"{i}. {recipe['name']} ({recipe['similarity_score']*100:.0f}% match)")

async def demo_api_features():
    """Show FastAPI specific features"""
    console.print("\n🎯 [bold cyan]FastAPI Features Demo[/bold cyan]\n")
    
    # Health check
    async with httpx.AsyncClient() as client:
        health = await client.get(f"{FASTAPI_URL}/")
        console.print("🏥 Health Check:", health.json()['status'])
        
        # Stats with proxy info
        stats = await client.get(f"{FASTAPI_URL}/stats")
        stats_data = stats.json()
        
        console.print("📈 Database:", f"{stats_data['stats']['total_recipes']} recipes")
        console.print("🔧 Search Method:", stats_data['stats']['search_method'])
        console.print("⚡ Cache Enabled:", stats_data['proxy_info']['cache_enabled'])

def show_api_docs_info():
    """Show API documentation links"""
    console.print("\n📚 [bold magenta]Auto-Generated API Documentation[/bold magenta]\n")
    
    docs_panel = Panel.fit(
        "[link=http://localhost:8001/docs]Interactive API Docs (Swagger)[/link]\n"
        "[link=http://localhost:8001/redoc]ReDoc Documentation[/link]\n\n"
        "Features:\n"
        "• ✅ Request/Response validation with Pydantic\n"
        "• ✅ Automatic OpenAPI schema generation\n" 
        "• ✅ Interactive testing interface\n"
        "• ✅ Type hints and error handling",
        title="Documentation Available"
    )
    console.print(docs_panel)

async def main():
    """Run the complete demo"""
    console.print("🎬 [bold red]FastAPI Proxy Demo - RAG Recipe Search[/bold red]")
    console.print("=" * 60)
    
    try:
        # Check if services are running
        async with httpx.AsyncClient() as client:
            fastapi_health = await client.get(f"{FASTAPI_URL}/", timeout=5)
            laravel_health = await client.get(f"{LARAVEL_URL}/search/stats", timeout=5)
        
        console.print("✅ FastAPI Proxy: Running on port 8001")
        console.print("✅ Laravel API: Running on port 8080")
        
        await demo_performance_comparison()
        await demo_enhanced_search()
        await demo_api_features()
        show_api_docs_info()
        
        console.print("\n🎉 [bold green]Demo Complete![/bold green]")
        console.print("💡 [italic]Perfect material for your 60-second Loom video![/italic]")
        
    except httpx.ConnectError as e:
        console.print(f"❌ Connection Error: {e}")
        console.print("Make sure both FastAPI (port 8001) and Laravel (port 8080) are running!")
    except Exception as e:
        console.print(f"❌ Error: {e}")

if __name__ == "__main__":
    asyncio.run(main())