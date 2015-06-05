<form action="" method="post">
    <div class="panel">
        <div class="panel-heading">{l s='List of export orders' mod='neoship'}</div>
        <div class="panel-wrapper">
            <table class="table">
                <thead>
                <tr>
                    <th rowspan="2">{l s='Number' mod='neoship'}</th>
                    <th rowspan="2">{l s='Date' mod='neoship'}</th>
                    <th rowspan="2">{l s='User' mod='neoship'}</th>
                    <th colspan="3">{l s='Notification' mod='neoship'}</th>
                    <th rowspan="2">{l s='Cash on delivery' mod='neoship'}</th>
                    <th colspan="2">{l s='Delivery' mod='neoship'}</th>
                    <th rowspan="2">{l s='Parts' mod='neoship'}</th>
                    <th rowspan="2">{l s='Main package' mod='neoship'}</th>
                    <th rowspan="2">{l s='Attachment' mod='neoship'}</th>
                </tr>
                <tr>
                    <th>{l s='SMS'}</th>
                    <th>{l s='Phone' mod='neoship'}</th>
                    <th>{l s='Email'}</th>
                    <th>{l s='Express' mod='neoship'}</th>
                    <th>{l s='Saturday' mod='neoship'}</th>
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
                        <td class="notification sms"><input type="checkbox"
                                                            name="ups-order[{$order['id_order']}][notification][sms]"
                                                            value="sms"/></td>
                        <td class="notification phone"><input type="checkbox"
                                                              name="ups-order[{$order['id_order']}][notification][phone]"
                                                              value="phone"/></td>
                        <td class="notification email"><input type="checkbox"
                                                              name="ups-order[{$order['id_order']}][notification][email]"
                                                              value="email" checked="checked"/></td>
                        <td class="cod">
                            <input type="checkbox" name="ups-order[{$order['id_order']}][cod-check]"/>
                            <input type="text" name="ups-order[{$order['id_order']}][cod]"
                                   value="{$order['total_paid']}"
                                   size="8" class="tar"/>
                        </td>
                        <td class="delivery express">
                            <select name="ups-order[{$order['id_order']}][express]">
                                <option value="">{l s='Standard delivery' mod='neoship'}</option>
                                <option value="1">{l s='Express 12' mod='neoship'}</option>
                            </select>
                        </td>
                        <td class="delivery saturday">
                            <input type="checkbox" name="ups-order[{$order['id_order']}][saturday]"/>
                        </td>
                        <td class="parts">
                            <input type="text" name="ups-order[{$order['id_order']}][parts]" size="2" value="1"/>
                        </td>
                        <td class="mainpackage">
                            <input type="checkbox" name="ups-order[{$order['id_order']}][mainpackage-check]">
                            <input type="text" name="ups-order[{$order['id_order']}][mainpackage]" size="12"/>
                        </td>
                        <td class="attachment">
                            <input type="checkbox" name="ups-order[{$order['id_order']}][attachment]">
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