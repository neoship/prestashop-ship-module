<form action="" method="post">
    <div class="panel">
        <div class="panel-heading">{l s='List of export orders' mod='neoship'}</div>
        <div class="panel-wrapper">
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Number' mod='neoship'}</th>
                    <th>{l s='Date' mod='neoship'}</th>
                    <th>{l s='User' mod='neoship'}</th>
                    <th colspan="2">{l s='Cash on delivery' mod='neoship'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $orders as $order}
                    {*{$order|@var_dump}*}
                    {*{$order['id_order']}*}
                    <tr>
                        <td>
                            <input type="text" name="ups-order[{$order['id_order']}][variablenumber]"
                                   value="{$order['reference']}"/>
                        </td>
                        <td class="date">{$order['date_add']}</td>
                        <td class="address">
                            {$order['deliveryName']}
                        </td>
                        <td class="cod">
                            <input type="checkbox" name="ups-order[{$order['id_order']}][cod-check]" />
                        </td>
                        <td class="cod">
                            <input type="text" name="ups-order[{$order['id_order']}][cod]"
                                   value="{$order['total_paid']}"
                                   size="8" class="tar"/>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            <button type="submit" name="exportOrders" value="1" class="btn btn-default pull-right">
                <i class="process-icon-upload"></i> {l s='Export'}
            </button>
            <a href="{$backLink}" class="btn btn-default"><i class="process-icon-back"></i>{l s='Back to list'}</a>
        </div>
    </div>
</form>