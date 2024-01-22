<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">

  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-invoice" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
       <?php if ($error_warning) { ?>
        <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i>  <?php echo $error_warning; ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
       <?php } ?>
    <div class="panel panel-default">

      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-invoice" class="form-horizontal">
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-invoice_key"><?php echo $entry_invoice_key ?></label>
            <div class="col-sm-10">
              <input type="text" name="invoice_key" value="<?php echo $invoice_key ?>" placeholder="<?php echo $entry_invoice_key ?>" id="input-invoice_key" class="form-control" />
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-invoice_login"><?php echo $entry_invoice_login ?></label>
            <div class="col-sm-10">
              <input type="text" name="invoice_login" value="<?php echo $invoice_login ?>" placeholder="79991234567" id="input-invoice_login" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status ?></label>
            <div class="col-sm-10">
              <select name="invoice_status" id="input-status" class="form-control">
                <?php if ($invoice_status) { ?>
                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                      <option value="0"><?php echo $text_disabled; ?></option>
                    <?php } else { ?>
                      <option value="1"><?php echo $text_enabled; ?></option>
                      <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<?php   echo $footer;  ?> 