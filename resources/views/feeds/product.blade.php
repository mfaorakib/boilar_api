<?=
/* Using an echo tag here so the `<? ... ?>` won't get parsed as short tags */
'<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><![CDATA[{{ $meta['title'] }}]]></title>
        <link><![CDATA[{{ url($meta['link']) }}]]></link>
        <atom:link href="{{ url($meta['link']) }}" rel="self" type="application/rss+xml"/>
        <description><![CDATA[{{ $meta['description'] }}]]></description>
        <language>{{ $meta['language'] }}</language>
        <pubDate>{{ $meta['updated'] }}</pubDate>

        @foreach($items as $item)
            <item>
                <g:google_product_category><![CDATA[{{ $item->categoryNames }}]]></g:google_product_category>
                <g:id><![CDATA[{{ $item->sku }}]]></g:id>
                <g:title><![CDATA[{{ $item->name }}]]></g:title>
                <g:description><![CDATA[{{ $item->excerpt }}]]></g:description>
                <g:link><![CDATA[{{ $item->url }}]]></g:link>
                <g:image_link>{{ $item->image }}</g:image_link>

                @foreach($item->images as $image)
                    <additional_image_link>{{ $image }}</additional_image_link>
                @endforeach


                <g:brand><![CDATA[{{ $item->brand }}]]></g:brand>
                <g:condition><![CDATA[{{ $item->condition }}]]></g:condition>

                <g:availability><![CDATA[{{ $item->availability }}]]></g:availability>

                <g:price>{{ $item->price }}</g:price>
                @if($item->has_offer)
                    <g:sale_price>{{ $item->special_price }}</g:sale_price>
                    <g:sale_price_effective_date>{{ $item->special_start_date .'/'. $item->special_end_date }}</g:sale_price_effective_date>
                @endif
                <g:applink>
                    <g:android_app_name><![CDATA[{{ $item->android_app_name }}]]></g:android_app_name>
                    <g:android_package>{{ $item->android_package }}</g:android_package>
                    <g:android_url>{{ $item->android_url }}</g:android_url>
                </g:applink>
            </item>
        @endforeach
    </channel>
</rss>
