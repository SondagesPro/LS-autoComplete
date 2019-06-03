<div class="row">
    <div class="col-lg-12 content-right">
        <h3><?php echo $title ?></h3>
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
            <?php echo CHtml::beginForm('', 'post', array('enctype'=>'multipart/form-data','class'=>"form-horizontal"));?>
            <fieldset>
                <legend><?php echo $lang["Create or update a new dataset."] ?></legend>
                <div class="form-group">
                    <?php echo CHtml::label($lang["Simple name for the auto complete questions."], 'autocomplete_name',array("class"=>"col-sm-5 control-label")); ?>
                    <div class="col-sm-7">
                    <?php echo CHtml::textField('autocomplete[name]','',array("class"=>"form-control")); ?>
                    </div>
                </div>
                <div class="form-group">
                    <?php echo CHtml::label($lang["Select your csv file name."], 'autocomplete_csv_file',array("class"=>"col-sm-5 control-label")); ?>
                    <div class="col-sm-7">
                    <?php echo CHtml::fileField('autocomplete[csv_file]','',array("class"=>"form-control",'style'=>'box-sizing: initial;box-shadow: initial;')); ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-7 col-md-offset-5">
                        <?php echo CHtml::submitButton(gT('Import'),array('class'=>'btn btn-primary')); ?>
                    </div>
                <?php echo CHtml::endForm(); ?> 
            </fieldset>
            <?php echo CHtml::endForm();?>
            </div>
        </div>
    </div>
</div>
