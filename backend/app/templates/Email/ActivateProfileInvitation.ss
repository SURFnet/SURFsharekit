<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Je bent uitgenodigd om een account aan te maken op SURFsharekit
    </p>

    <% include Email\Components\Button Text="Account activeren", ButtonLink=$ProfileLink %>

    <p>
        Wil je dit niet? Neem contact op via <a href="mailto:info@surfsharekit.nl">info@surfsharekit.nl</a>.
    </p>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        You have been invited to create an account on SURFsharekit
    </p>

    <% include Email\Components\Button Text="Activate account", ButtonLink=$ProfileLink %>

    <p>
        Not interested? Contact <a href="mailto:info@surfsharekit.nl">info@surfsharekit.nl</a>.
    </p>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>