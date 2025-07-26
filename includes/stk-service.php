<?php

function kkwoo_send_stk_push($phone, $order_id)
{
    // TODO: Integrate with K2 here
    // Fetch order info here
    $order = wc_get_order($order_id);

    // Example: Send dummy success
    return true;
}
