{if !isset($result) && $result.length == 0}
    <form action="" method="post">
        <div class="panel">
            <div class="panel-heading">{l s='Neoship export'}</div>
            <div class="panel-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th rowspan="2">{l s='Number'}</th>
                        <th rowspan="2">{l s='Date'}</th>
                        <th rowspan="2">{l s='User'}</th>
                        <th colspan="3">{l s='Notification'}</th>
                        <th rowspan="2">{l s='Cash on delivery'}</th>
                        <th colspan="2">{l s='Delivery'}</th>
                        <th rowspan="2">{l s='Parts'}</th>
                        <th rowspan="2">{l s='Main package'}</th>
                        <th rowspan="2">{l s='Attachment'}</th>
                    </tr>
                    <tr>
                        <th>{l s='SMS'}</th>
                        <th>{l s='Phone'}</th>
                        <th>{l s='Email'}</th>
                        <th>{l s='Express'}</th>
                        <th>{l s='Saturday'}</th>
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
                                    <option value="">{l s='Standard delivery'}</option>
                                    <option value="1">{l s='Express 12'}</option>
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
                <a href="{$backLink}" class="btn btn-default"><i class="process-icon-back"></i> Back to list</a>
            </div>
        </div>
    </form>
{else}
    <div class="panel">
        <div class="panel-heading">{l s='Result of Neoship API export'}</div>
        <div class="panel-wrapper">
            <table class="sortable quicksearch">
                <thead>
                <tr>
                    <th>{l s='Number'}</th>
                    <th>{l s='User'}</th>
                    <th>{l s='Parts'}</th>
                    <th>{l s='Status'}</th>
                    <th>{l s='Detail'}</th>
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
                                <strong>{l s='Cash on delivery'}:</strong>
                                {$res['data']['package']['cashOnDeliveryPrice']}
                                <br/>
                            {/if}
                        </td>
                        <td>
                            {if isset($res['result']) }
                                {$result|count}
                            {/if}
                        </td>
                        <td>
                            {if isset($res['exception']) }
                                {l s='Exception'}
                            {else}
                                {l s='OK'}
                            {/if}
                        </td>
                        <td
                            {if isset($res['exception']) }class="flash-error"{/if}>
                            {if isset($res['exception']) && !empty($res['exception'])}
                                {foreach $res['exception'] as $key => $ex}
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
{/if}
