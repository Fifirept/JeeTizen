<?php
if (!isConnect('admin')) {
    throw new Exception('401 - Accès non autorisé');
}
?>
<div class="row">
    <div class="col-lg-2 eqLogicThumbnailDisplay">
        <div class="input-group">
            <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
            <div class="input-group-btn">
                <button id="bt_addEqLogic" class="btn btn-default"><i class="fa fa-plus-circle"></i></button>
                <button class="btn btn-default btn-sm" id="bt_pluginTemplate" data-lang="Plugin"><i class="fa fa-cogs"></i></button>
            </div>
        </div>
        <br/>
        <div class="eqLogicThumbnailContainer">
        </div>
    </div>
    <div class="col-lg-10 eqLogicDisplayPanel" style="display:none">
        <div class="input-group pull-right" style="display:inline-flex">
            <a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
            <a class="btn btn-success btn-sm eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
            <a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
        </div>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab">{{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab">{{Commandes}}</a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php foreach (jeeObject::all() as $obj) { ?>
                                    <option value="<?php echo $obj->getId(); ?>"><?php echo str_repeat('&nbsp;&nbsp;', $obj->getConfiguration('parentNumber',0)) . $obj->getName(); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Catégorie}}</label>
                            <div class="col-sm-6">
                                <?php foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) { ?>
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="<?php echo $key; ?>"/>
                                    {{<?php echo $value['name']; ?>}}
                                </label>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Activer}}</label>
                            <div class="col-sm-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                            </div>
                            <label class="col-sm-2 control-label">{{Visible}}</label>
                            <div class="col-sm-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                            </div>
                        </div>
                    </fieldset>
                </form>
                <legend>{{Connexion TV Samsung}}</legend>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Adresse IP}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip" placeholder="192.168.1.x"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Port}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port" placeholder="8002"/>
                            </div>
                            <label class="col-sm-1 control-label">{{SSL}}</label>
                            <div class="col-sm-1" style="margin-top:7px">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="ssl"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Token Auth}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="tokenAuth" placeholder="{{Rempli automatiquement}}"/>
                            </div>
                            <div class="col-sm-2">
                                <button type="button" class="btn btn-warning btn-sm" id="bt_clearToken"><i class="fa fa-trash"></i> {{Réinitialiser token}}</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
                <legend>{{Options Zap}}</legend>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Source retour après zap}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="sourceRetour">
                                    <option value="">{{Rester sur TNT}}</option>
                                    <option value="HDMI1">HDMI 1</option>
                                    <option value="HDMI2">HDMI 2</option>
                                    <option value="HDMI3">HDMI 3</option>
                                    <option value="HDMI4">HDMI 4</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Délai retour source (ms)}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="delayRetour" placeholder="3000"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Délai inter-touches (ms)}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="keyDelay" placeholder="300"/>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{Nom}}</th>
                            <th>{{Sous-type}}</th>
                            <th>{{Action}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_file('desktop', 'JeeTizen', 'js', 'JeeTizen'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
