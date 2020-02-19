<!DOCTYPE html>
<html lang="en">
<head>
    <title>sms77io</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{link file="backend/_resources/css/bootstrap.min.css"}">
</head>
<body role="document" style="padding-top: 80px">

<div class="container theme-showcase" role="main">
    <img src="{link file="backend/_resources/img/sms77-Logo-400x79.png"}" alt="sms77io Logo"/>

    {block name="content/main"}{/block}
</div>

<script src="{link file="backend/_resources/js/jquery-2.1.4.min.js"}"></script>
<script src="{link file="backend/_resources/js/bootstrap.min.js"}"></script>

{block name="content/layout/javascript"}
    <script>
        $(function () {
            const $type = $('fieldset[name="type"] input[type="radio"]');

            $type.on('change', function () {
                $type.not(this).prop('checked', false);
            });
        });
    </script>
{/block}
{block name="content/javascript"}{/block}
</body>
</html>