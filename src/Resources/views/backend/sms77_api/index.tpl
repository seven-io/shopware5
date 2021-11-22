{namespace name="backend/sms77_api"}

{extends file="parent:backend/_base/layout.tpl"}

{block name="content/main"}
    <div class="page-header">
        <h1>{s name="sms77_api/sms/bulk"}{/s}</h1>
    </div>
    {if $infos|@count}
        <div class="alert alert-info alert-dismissible" role="alert">
            <button class="close" data-dismiss="alert"
                    aria-label="{s name="sms77_api/close"}{/s}"><span
                        aria-hidden="true">&times;</span></button>

            <ul>
                {foreach from=$infos item=info}
                    <li>{$info}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {if isset($sent) && $sent|@count}
        <div class="alert alert-success alert-dismissible" role="alert">
            <button class="close" data-dismiss="alert"
                    aria-label="{s name="sms77_api/close"}{/s}"><span
                        aria-hidden="true">&times;</span></button>

            <ul>
                {foreach from=$sent item=s}
                    <li>{$s|@json_encode}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {if isset($failed) && $failed|@count}
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button class="close" data-dismiss="alert"
                    aria-label="{s name="sms77_api/close"}{/s}"><span
                        aria-hidden="true">&times;</span></button>

            <ul>
                {foreach from=$failed item=f}
                    <li>{$f|@json_encode}</li>
                {/foreach}
            </ul>
        </div>
    {/if}
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{s name="sms77_api/sms/bulk"}{/s}</h3>
        </div>

        <div class="panel-body">
            <p>{s name="sms77_api/sms/teaser"}{/s}</p>

            {if {config name=sms77apiKey}|count_characters}
                <form class="form-horizontal sms77-bulk-form" method="post">
                    <div class="form-group">
                        <label for="text" class="col-sm-2 control-label">
                            {s name="sms77_api/msg_content"}{/s}
                        </label>

                        <div class="col-sm-10">
                            <textarea id="text" class="form-control" name="text"
                                      required></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="customerGroups" class="col-sm-2 control-label">
                            {s name="sms77_api/customer_groups"}{/s}
                        </label>

                        <div class="col-sm-10">
                            <select class="form-control" id="customerGroups"
                                    name="customerGroups[]" multiple>
                                {foreach from=$customerGroups item=group}
                                    <option value="{$group['id']}">{$group['label']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="countries" class="col-sm-2 control-label">
                            {s name="sms77_api/countries"}{/s}
                        </label>

                        <div class="col-sm-10">
                            <select id="countries" class="form-control" name="countries[]"
                                    multiple>
                                {foreach from=$countries item=country}
                                    <option value="{$country['id']}">{$country['label']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="from" class="col-sm-2 control-label">
                            {s name="sms77_api/from"}{/s}
                        </label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="from" name="from"
                                   value="{config name=sms77from}" />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary">
                                {s name="sms77_api/send"}{/s}
                            </button>
                        </div>
                    </div>
                </form>
            {else}
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button class="close" data-dismiss="alert"
                            aria-label="{s name="sms77_api/close"}{/s}"><span
                                aria-hidden="true">&times;</span></button>

                    <p>
                        {s name="sms77_api/api_key_required"}{/s}
                    </p>
                </div>
            {/if}
        </div>
    </div>
{/block}
