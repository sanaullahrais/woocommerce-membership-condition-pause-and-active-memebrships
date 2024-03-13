/* If you are looking a way to allow user to get a free membership then upgrade to other membership then the below code help you with this.

When someone completes a purchase that includes a membership, the system checks if they already have an active membership. If they do, it automatically pauses the older membership and activates the new one once the order is completed. This ensures that our customers can upgrade or change their memberships seamlessly without any overlap or confusion. Additionally, when an order is initially processed, the system temporarily pauses the new membership until the order is fully completed, preventing any issues with multiple active memberships. It's a smooth way to make sure everything regarding memberships is handled neatly and efficiently, giving customers the best experience possible.

Hope this will help you if you are planning to allow user to assign the free membership once user register and if they want to upgrade to another membership then the below code will help you to achieve this.

Make sure you assign the free membership manually not in registration directly, if you want to allow the free membership registration then create a separate registration form for this. So for the paid memebrship once they click they should get redirect to checkout page and after successful purchase the membership will be assigned to them and you have to change the order status from processing to completed to make the membership active. :)
*/

// Membership Switch - Old Membership will be paused once the new membership become active
add_action('woocommerce_order_status_completed', 'handle_memberships_on_order_complete');

function handle_memberships_on_order_complete($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = $order->get_user_id();
    if ($user_id <= 0) return;

    // Ensure the WooCommerce Memberships plugin is active
    if (!function_exists('wc_memberships_get_user_memberships') || !function_exists('wc_memberships_get_user_membership')) {
        return;
    }

    $memberships = wc_memberships_get_user_memberships($user_id, array('status' => 'active'));
    if (empty($memberships)) return;

    // Sort memberships by start date to identify the most recent
    usort($memberships, function($a, $b) {
        return strtotime($a->get_start_date('edit')) - strtotime($b->get_start_date('edit'));
    });

    $latest_membership = end($memberships); // Get the latest membership
    $latest_membership_id = $latest_membership ? $latest_membership->get_id() : null;

    foreach ($memberships as $membership) {
        // If an older active membership is found that is not the latest, pause it
        if ($membership->get_id() != $latest_membership_id) {
            $membership->update_status('wcm-paused');
        }
    }

    // Ensure the latest membership related to this order is set to active
    // Check if the latest membership is linked to the current order
    if ($latest_membership && $latest_membership->get_order_id() == $order_id) {
        $latest_membership->update_status('wcm-active');
    }
}



//As the woocommerce order first goes to processing to this will keep the new membership as a paused. Once the status is changes to complete this will pause the old membership and will
//make the new membership active
add_action('woocommerce_order_status_processing', 'pause_memberships_for_processing_orders');

function pause_memberships_for_processing_orders($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // Check if WooCommerce Memberships is active
    if (!function_exists('wc_memberships_get_user_memberships')) {
        return;
    }
    
    $user_id = $order->get_user_id();
    
    // Proceed only if there's a logged-in user associated with the order
    if ($user_id <= 0) {
        return;
    }
    
    $memberships = wc_memberships_get_user_memberships($user_id, array('status' => 'wcm-active'));
    
    foreach ($memberships as $membership) {
        // Check if the membership is linked to the order being processed
        $linked_order_id = $membership->get_order_id();
        
        if ($linked_order_id == $order_id) {
            // Check if the membership was just activated (you may need to refine this logic based on your setup)
            // Here we assume we're pausing all active memberships linked to this order
            $membership->update_status('wcm-paused');
        }
    }
}
