<% if $isLive %>
<% else_if $isDev %>
    <div style="background-color: darkgreen; height: 50px; color: white; font-size: 30px; display: flex;justify-content: center;align-items: center;">
        DEVELOPMENT ENVIRONMENT
    </div>
<% else_if $isTest %>
    <div style="background-color: orange; height: 50px; color: white; font-size: 30px; display: flex;justify-content: center;align-items: center;">
        TEST ENVIRONMENT
    </div>
<% else_if $isStaging %>
    <div style="background-color: orangered; height: 50px; color: white; font-size: 30px; display: flex;justify-content: center;align-items: center;">
        STAGING ENVIRONMENT
    </div>
<% else_if $isAcceptance %>
    <div style="background-color: red; height: 50px; color: white; font-size: 30px; display: flex;justify-content: center;align-items: center;">
        ACCEPTANCE ENVIRONMENT
    </div>
<% end_if %>
