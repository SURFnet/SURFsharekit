<div>
    <p>Beste $Receiver.FullName,</p>

    <p>
        Het door jou geïnitieerde saneerproces op $Time is succesvol afgerond.
    </p>
    <p>
        Totaal geselecteerde publicaties: $TotalCount <br>
        Succesvol aangepaste publicaties: $SuccessCount <br>
        Niet aangepaste publicaties: $FailCount
    </p>
    <p>
        Als er publicaties zijn die niet zijn aangepast, kan dit komen omdat je niet de juiste rechten hebt om
        de door jouw geselecteerde actie uit te voeren op deze publicaties of omdat er tijdens het saneerprocess mogelijk iets fout gegaan is.
    </p>

    <% include Email\Components\Button Text="Ga naar dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink %>
    <% include Email\Defaults\Wiki %>

    <% include Email\Components\divider %>

    <p>Dear $Receiver.FullName,</p>

    <p>
        The remediation process that was initiated by you on $Time is completed.
    </p>
    <p>
        Total selected publications: $TotalCount <br>
        Successfully edited publications: $SuccessCount <br>
        Unedited publications: $FailCount
    </p>
    <p>
        Any unedited publications could be the consequence of you not having the rights to perform the selected action
        on these publications or because something went wrong during the remediation process.
    </p>

    <% include Email\Components\Button Text="Go to dashboard", ButtonLink=$DashboardLink %>

    <% include Email\Defaults\EmailPreferences PreferencesLink=$PreferencesLink, Lang="en" %>
    <% include Email\Defaults\Wiki Lang="en" %>
</div>