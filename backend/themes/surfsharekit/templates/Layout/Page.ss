<div class="content-container unit size3of4 lastUnit">
    <% if $PasswordSent %>
        <div id="password-sent">You will receive an email that contains a password reset link.</div>
    <% else %>
        $Form
    <% end_if %>
</div>