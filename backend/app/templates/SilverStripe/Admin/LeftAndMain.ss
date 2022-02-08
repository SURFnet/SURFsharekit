<!DOCTYPE html>

<html lang="$Locale.RFC1766">
<head>
    <% base_tag %>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, maximum-scale=1.0"/>
    <title>$Title</title>
</head>
<body class="loading cms" data-frameworkpath="$ModulePath(silverstripe/framework)"
      data-member-tempid="$CurrentMember.TempIDHash.ATT"
>
    <% include SilverStripe\\Admin\\CMSLoadingScreen %>
<div style="display: flex;flex-direction: column;">
    <% include SilverStripe\\Admin\\EnvironmentBanner %>

    <% if $isLive %>
    <div class="cms-container" data-layout-type="custom">
    <% else %>
    <div class="cms-container" data-layout-type="custom" style="height: calc(100%  - 50px)">
    <% end_if %>
    $Menu
    $Content
    $PreviewPanel
</div>
</div>

    <% include SilverStripe\\Admin\\BrowserWarning %>
</body>
</html>
