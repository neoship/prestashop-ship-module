<div class="panel">
    <div class="panel-heading">{l s='Result of Neoship API export' mod='neoship'}</div>
    <div class="panel-wrapper">
        <table class="table">
            <thead>
            <tr>
                <th>{l s='Number' mod='neoship'}</th>
                <th>{l s='User' mod='neoship'}</th>
                <th>{l s='Parts' mod='neoship'}</th>
                <th>{l s='Status' mod='neoship'}</th>
                <th>{l s='Detail' mod='neoship'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $result as $res}
                <tr>
                    <td>{$res['data']['package']['variableNumber']}</td>
                    <td>
                        {$res['data']['package']['reciever']['name']}, {$res['data']['package']['reciever']['city']}
                        <br/>
                        {if isset($res['data']['package']['cashOnDeliveryPrice']) }
                            <strong>{l s='Cash on delivery' mod='neoship'}:</strong>
                            {$res['data']['package']['cashOnDeliveryPrice']}
                            <br/>
                        {/if}
                    </td>
                    <td>
                        {if isset($res['result']) }
                            {$res['result']|count}
                        {/if}
                    </td>
                    <td>
                        {if isset($res['exception']) }
                            <span class="badge badge-danger">{l s='Exception' mod='neoship'}</span>
                        {else}
                            <span class="badge badge-success">{l s='OK'}</span>
                        {/if}
                    </td>
                    <td>
                        {if isset($res['exception']) && !empty($res['exception'])}
                            {foreach $res['exception'] as $ex}
                                {$ex}
                            {/foreach}
                        {/if}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <a href="{$backLink}" class="btn btn-default"><i class="process-icon-back"></i> Back to list</a>
    </div>
</div>