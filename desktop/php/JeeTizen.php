<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('JeeTizen');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-tv"></i> {{Mes TV Samsung}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement JeeTizen trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div>

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet Equipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<!-- Colonne gauche : paramètres généraux + connexion TV -->
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Connexion TV Samsung}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Modèle TV}}</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="modele_tv" id="sel_modele_tv">
										<option value="tizen">{{Tizen (2016+)}}</option>
										<option value="legacy">{{Legacy (pré-2016)}}</option>
									</select>
								</div>
							</div>
							<div class="form-group" id="id_sub_modele_tv_group">
								<label class="col-sm-4 control-label">{{Série}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Standard : port 8001 (WS) ou 8002 (WSS). Modèle K : port 8002 + délai. Modèle J : nécessite daemon.}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="sub_modele_tv" id="id_sel_sub_modele">
										<option value="0">{{Standard}}</option>
										<option value="1">{{Modèle K (2016)}}</option>
										<option value="2">{{Modèle J (encrypted)}}</option>
										<option value="3">{{Défaut (auto)}}</option>
									</select>
								</div>
							</div>
							<div class="form-group" id="id_sub_modele_delay" style="display:none;">
								<label class="col-sm-4 control-label">{{Délai Modèle K (ms)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Délai spécifique modèle K, entre 500 et 1000 ms}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="sub_modele_tv_delay" placeholder="500">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Adresse IP TV}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip_tv" placeholder="192.168.x.x">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Port TV}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port_tv" placeholder="8002" id="id_port_tv">
								</div>
							</div>
							<div class="form-group" id="ssl_tv">
								<label class="col-sm-4 control-label">{{SSL (WSS)}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="ssl_tv">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Token d'authentification}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Rempli automatiquement après la première connexion. Acceptez l'appairage sur la TV.}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<div class="input-group">
										<input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="tokenAuth" placeholder="{{Automatique}}" readonly>
										<span class="input-group-btn">
											<a class="btn btn-warning roundedRight" id="bt_resetToken" title="{{Réinitialiser le token}}">
												<i class="fas fa-sync-alt"></i>
											</a>
										</span>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Application TV}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="app_tv" placeholder="jeedom.jeetizen.samsung">
								</div>
							</div>
						</div>

						<!-- Colonne droite : WOL + latences + description -->
						<div class="col-lg-6">
							<legend><i class="fas fa-power-off"></i> {{Wake On LAN}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Activer WOL}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="wol_tv" id="chk_wol_tv">
								</div>
							</div>
							<div class="form-group wol_options" style="display:none;">
								<label class="col-sm-4 control-label">{{Adresse MAC TV}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="adresse_mac_tv" placeholder="AA:BB:CC:DD:EE:FF">
								</div>
							</div>
							<div class="form-group wol_options" style="display:none;">
								<label class="col-sm-4 control-label">{{Mode WOL}}</label>
								<div class="col-sm-6">
									<label class="radio-inline">
										<input type="radio" name="wol_type" class="eqLogicAttr" data-l1key="configuration" data-l2key="wol_tv_direct" value="1"> {{Direct}}
									</label>
									<label class="radio-inline">
										<input type="radio" name="wol_type" class="eqLogicAttr" data-l1key="configuration" data-l2key="wol_tv_broadcast" value="1"> {{Broadcast}}
									</label>
								</div>
							</div>
							<div class="form-group wol_direct_options" style="display:none;">
								<label class="col-sm-4 control-label">{{IP Broadcast direct}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="wol_broadcast_ip_direct" placeholder="192.168.1.255">
								</div>
							</div>
							<div class="form-group wol_broadcast_options" style="display:none;">
								<label class="col-sm-4 control-label">{{Subnet masque}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="wol_broadcast_subnet" placeholder="255.255.255.0">
								</div>
							</div>

							<legend><i class="fas fa-clock"></i> {{Latences}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Latence touches (ms)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Délai entre touches non numériques. 0-2000 ms.}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="scenario_tps_pause" placeholder="100">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Latence NUM (ms)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Délai entre touches numériques (zap). 1-2000 ms.}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="scenario_tps_pause_num" placeholder="500">
								</div>
							</div>

							<legend><i class="fas fa-palette"></i> {{Widget télécommande}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Template}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Choisir le style de la télécommande sur le dashboard et le design. 'Aucun' affiche les boutons Jeedom standards.}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="widget_template">
										<option value="dark">{{Sombre}}</option>
										<option value="light">{{Clair}}</option>
										<option value="none">{{Aucun (standard Jeedom)}}</option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Taille widget}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Taille de la telecommande : 30, 50, 75 ou 100% (defaut)}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="widget_scale">
										<option value="100">{{100% (normal)}}</option>
										<option value="75">{{75%}}</option>
										<option value="50">{{50%}}</option>
										<option value="30">{{30%}}</option>
									</select>
								</div>
							</div>

							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<?php include_file('desktop', 'JeeTizen', 'css', 'JeeTizen'); ?>
<?php include_file('desktop', 'JeeTizen', 'js', 'JeeTizen'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
