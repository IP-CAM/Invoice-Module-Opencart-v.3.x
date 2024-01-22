<div class="buttons">
  <form method="POST" action="<?php echo $confirm_url ?>" id='paymentForm'>
    <div class="pull-right">
      <input type="submit" value="<?php echo $button_confirm ?>" id="button-confirm" class="btn btn-primary" data-loading-text="<?php echo "Оплатить" ?>" />
      <input type="hidden" name="order_id" value="<?php echo $order_id ?>"/>
    </div>
  </form>
</div>
