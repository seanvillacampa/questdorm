<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
@php
    // Get the slot content
    $content = (string) $slot;
    
    // Check if this is an action URL subcopy (contains "If you're having trouble")
    if (str_contains($content, "If you're having trouble clicking")) {
        // Extract the button text and URL
        preg_match('/clicking the "([^"]+)" button/', $content, $matches);
        $buttonText = $matches[1] ?? 'button';
        
        // Replace with our custom message
        echo "<p>If you're having trouble clicking the \"{$buttonText}\" button, copy and paste the URL below into your web browser: <strong>https://questdorm.onrender.com</strong>, login to your tenant portal, and go to <strong>My Bill</strong>.</p>";
        
        // Extract and show the URL
        preg_match('/<a[^>]*href="([^"]*)"[^>]*>([^<]*)<\/a>/', $content, $urlMatches);
        if (!empty($urlMatches[1])) {
            echo "<p><a href=\"{$urlMatches[1]}\">{$urlMatches[1]}</a></p>";
        }
    } else {
        // For non-action subcopies, just render as normal
        echo Illuminate\Mail\Markdown::parse($content);
    }
@endphp
</td>
</tr>
</table>
