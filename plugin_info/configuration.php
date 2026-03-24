<?php
if (!isConnect('admin')) {
    throw new Exception('401 - Accès non autorisé');
}
?>
<form class="form-horizontal">
    <fieldset>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Niveau de log}}</label>
            <div class="col-sm-3">
                <select class="configKey form-control" data-l1key="logLevel">
                    <option value="100" <?php echo (config::byKey('logLevel','JeeTizen','100') == '100') ? 'selected' : ''; ?>>{{Défaut}}</option>
                    <option value="1000" <?php echo (config::byKey('logLevel','JeeTizen','100') == '1000') ? 'selected' : ''; ?>>{{Debug}}</option>
                    <option value="200" <?php echo (config::byKey('logLevel','JeeTizen','100') == '200') ? 'selected' : ''; ?>>{{Info}}</option>
                    <option value="300" <?php echo (config::byKey('logLevel','JeeTizen','100') == '300') ? 'selected' : ''; ?>>{{Warning}}</option>
                    <option value="400" <?php echo (config::byKey('logLevel','JeeTizen','100') == '400') ? 'selected' : ''; ?>>{{Error}}</option>
                </select>
            </div>
        </div>
    </fieldset>
</form>
