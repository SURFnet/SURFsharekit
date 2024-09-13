<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
    <% if $SiteConfig.Title %>
        <title>$SiteConfig.Title: <%t SilverStripe\LoginForms.LOGIN "Log in" %></title>
        $Metatags(false).RAW
    <% else %>
        $Metatags.RAW
    <% end_if %>
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <meta name="color-scheme" content="light only" />
    <% require css("silverstripe/admin: client/dist/styles/bundle.css") %>
    <% require themedCSS('login_new') %>
</head>
<body>
<%--<% include AppHeader %>--%>

<main class="login-form">

    <div class="login-form__header">
        <img id="login-brand" src="/_resources/themes/surfsharekit/images/surf_sharekit_logo.jpg" alt="SURFsharekit icon">
    </div>

    <% if $Message %>
        <p class="login-form__message
                    <% if $MessageType && not $AlertType %>login-form__message--$MessageType<% end_if %>
            <% if $AlertType %>login-form__message--$AlertType<% end_if %>"
        >
            $Message
        </p>
    <% end_if %>

    <% if $Content && $Content != $Message %>
        <div class="login-form__content">
            $Content
        </div>
    <% end_if %>

    $Form
</main>
</body>
</html>
