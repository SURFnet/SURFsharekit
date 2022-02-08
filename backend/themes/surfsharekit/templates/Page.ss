<!DOCTYPE html>
<!--[if !IE]><!--><html lang="$ContentLocale"><!--<![endif]-->
<!--[if IE 7 ]><html lang="$ContentLocale" class="ie ie7"><![endif]-->
<!--[if IE 8 ]><html lang="$ContentLocale" class="ie ie8"><![endif]-->
<!--[if gt IE 8]><html lang="$ContentLocale"><![endif]-->

<head>
	<% base_tag %>
    <title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> &raquo; $SiteConfig.Title</title>
    <% include MetaInfo %>
    <% include SharedCSS %>
    <% require themedCSS('login') %>
</head>
<body class="$ClassName">
	<div class="main" role="main">
		$Layout
	</div>
</body>
</html>
