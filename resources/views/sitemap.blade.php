<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('shop.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach ($products as $product)
    <url>
        <loc>{{ route('shop.product', $product->id) }}</loc>
        <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>
