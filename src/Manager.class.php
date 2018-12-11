<?php
	class Manager{
		/**
		 * Valida si existe el usuario identificado por el usuario y password recibido. Si el usuario existe informa la sesiÃ³n con
		 * los datos de Ã©ste y devuelve true, si no existe devuleve false.
		 *
		 * @param String $usr	Nombre de usuario
		 * @param String $pwd	Contraseña.
		 *
		 * @return boolean		Si existe un usuario en la base de datos que coincida con la info del login.
		 */
		public static function checkLogin($usr, $pwd){

			//echo "select * from v_users u where u.userUser='$usr' and u.userPwd='".md5($pwd)."'";

			$login 		= false;
			$userBean	= ManagerHelper::getBean("select * from v_users u where u.userUser='$usr' and u.userPwd='".md5($pwd)."'", 'user' );
			//$userBean	= ManagerHelper::getBean("select * from users u where u.usr='$usr' and u.pwd='".md5($pwd)."'", 'user' );

			if($userBean != NULL){
				//print_r($userBean);
				ManagerUtils::logUser($userBean);

				$login = true;
			}

			return $login;
		}

		/**
		 *
		 * Carga la página correspondiente del listado de usuarios con el orden, filtros y paginación correpondientes.
		 *
		 * @param $pageNumber
		 * @param $orderColumn
		 * @param $orderType
		 * @param $searchUsr
		 * @param $searchName
		 * @param $searchMail
		 * @param $searchTlf
		 * @param $searchFax
		 * @param $searchUserType
		 * @param $searchAdminFk
		 * @param $searchEm1Fk
		 * @param $searchEm2Comms
		 *
		 * @return ShowUsersBean (	$fullAdminBeanList, $searchUsr, $searchName, $searchMail, $searchTlf, $searchFax,
		 * 							$searchUserType, $userTypeBeans, $searchAdminFk, $adminBeans, $searchEm1Fk,
		 * 							$em1Beans, $searchEm2Comms, $em2Beans, $submitAction))
		 */
		public static function showUsers(	$pageNumber, $orderColumn, $orderType,
											$h1,
											$usersView, $searchSuperCommColumn, $searchSuperCommFk,
											$searchAdminFk, $searchEm1Fk, $searchEm2Fk,
											$searchUsr, $searchName, $searchMail, $searchTlf, $searchFax, $searchUserType,
											$itemsPerPage){
			Validator::validateSession();
			$userTypeBeans 		= array();
			$adminBeans			= array();
			$em1Beans			= array();
			$em2Beans			= array();
			$sql				= ManagerUtils::getUsersSql($usersView, $searchSuperCommColumn, $searchSuperCommFk, $searchUsr,
															$searchName, $searchMail, $searchTlf, $searchFax, $searchUserType);
			$fullUserDaosList 	= SQLHelper::getPageList($sql, $orderColumn, $orderType, $pageNumber, 'user');
			$fullUserBeansList	= P2P::fullDaoList($fullUserDaosList);
			if(Validator::isSuperUserInSession()){
				$userTypeBeans 	= ManagerHelper::getComboBeans($view="v_user_types_combo", $superValue="");
				/* Recuperamos la lista de administradores para la select de filtro por administrador.*/
				$adminBeans		= ManagerHelper::getComboBeans($view="v_admins_combo", $superValue="");
				/* Recuperamos la lista de em1 para la select de filtro por em1.*/
				$em1Beans		= ManagerHelper::getComboBeans($view="v_em1_combo", $superValue="");
				/* Recuperamos la lista de em1 para la select de filtro por em1.*/
				$em2Beans		= ManagerHelper::getComboBeans($view="v_em2_combo", $superValue="");
			}
			/* Construimos el bean de presentación con toda la info necesaria para mostrar el listado de administradores*/
			$showUsersBean		= new ShowUsersBean($fullUserBeansList, $searchUsr, $searchName, $searchMail, $searchTlf,
													$searchFax, $searchUserType, $userTypeBeans, $searchAdminFk, $adminBeans,
													$searchEm1Fk, $em1Beans, $searchEm2Fk, $em2Beans,
													$submitAction="showUsers");

			return $showUsersBean;
		}

		/**
		 * Recupera la info necesaria para mostrar el formulario de alta de un usuario (todos los campos vacíos excepto las selects
		 * que se cargan con la correspondiente información en bd)
		 *
	 	 * @return EditUserBean($userBean, $superCommBeans, $userTypeBeans, $adminBeans, $em1Beans, $em2Beans)
	 	 *
		 */
		public static function addUser(){
			$userBean 			= new UserBean($userOid="", $usr="", $pwd="", $mail="", $tlf="", $fax="", $name="", $userTypeOid="", $itemsPerPage="");
			$superCommBeans		= NULL;

			$editUserBean	= ManagerUtils::fillEditUserBean($userBean, $superCommBeans, $newUser=true, $loadCombos=true);

			return $editUserBean;
		}

		/**
		 * Guarda en bd la información del usuario a crear. Si el parámetro userOid llega informado se utiliza este como identificador
		 * en la tabla users de la bd. En caso contrario se le asigna uno negativo, calculado a partir del userOid menor y restándole 1.
		 *
		 * @param String $usr
		 * @param String $pwd
		 * @param String $name
		 * @param String $userTypeFk
	 	 * @param String $mail
	 	 * @param String $tlf
	 	 * @param String $userFk
	 	 *
	 	 * @return 		EditUserBean ($usr, $pwd, $name, $userTypeFk, $mail, $tlf, $userFk)
	 	 *
	 	 * @exception	Si el nombre de usuario ya existe en la base de datos.
	 	 *
		 */
		public static function insertUser($userOid, $pwd, $name, $userTypeFk, $mail, $tlf, $fax, $envioAdicional, $adminFk, $em1Fk, $em2Fk){
			$isCommType	= $userTypeFk==Cte::$COMM;
			if($userOid == ""){
				/* Si el usuario no es de tipo comunidad el id se obtiene como el id más bajo -1 (para evitar colisionar con los id de comms)*/
				$userOid = ManagerUtils::findMinUserOid() - 1;
				/* Lo metemos en request que nos hará falta al redirigir al action de edición de usuario*/
				$_REQUEST['__userOid']	= $userOid;
			}
			$usr	= "user".$userOid;
			$sql = "insert into users(userId, usr, pwd, name, mail, tlf, fax, envio_adicional, userTypeFk) values ($userOid, '$usr', '".md5($pwd)."', '$name', '$mail', '$tlf', '$fax', '$envioAdicional', $userTypeFk) ";
			SQLHelper::executeStatement($sql);
			/* Actualizamos el histórico de superusuarios. Sólo tendrá algún efecto si el usuario creado es COMM y se indica algún superusuario*/
			ManagerUtils::insertURH($userTypeFk, $userOid, $adminFk, $em1Fk, $em2Fk);
		}

		/**
		 *
		 * Dado un identificador de usuario recupera la información de éste en la bd y el resto de información necesaria para la
		 * vista de edición de usuarios y lo devuelve encapsulado en un bean.
		 *
		 * @param Number		$userOid			Identificador del usuario a editar.
		 * @param Number		$userTypeId		Tipo del usuario a editar.
		 *
	 	 * @return EditUserBean ($usr, $pwd, $name, $userTypeFk, $mail, $tlf, $userFk)
		 */
		public static function editUser($userOid, $userTypeId){
			/* Inicializamos a NULL todas las variables que nos van a hacer falta para el constructor del bean de presentación y que no sabemos si se informarán más adelante (según algunas condiciones se informan unas u otras)*/
			$superCommBeans=$adminBeans=$em1Beans=$em2Beans=$subCommBeans=$otherCommBeans=NULL;
			if($userTypeId == Cte::$COMM){
				/* Si es de tipo comunidad cargaremos la info de los usuarios con los que puede relacionarse (admins, em 1 y 2)*/
				/* 1.-info del usuario*/
				$sql 		= "select * from v_users where userOid=$userOid";
				$userBean	= ManagerHelper::getBean($sql, 'user');

				/* 2.-Info de los usuarios con los que ésté relacionado (puede que ninguno y en ningún caso más de tres). No usamos vistas por que no hay un única que incluya comunidades, admins y ems*/
				$sql 	 = "select * from v_users u, usersrelhistory urh ";
				$sql	.= "where urh.commFk=$userOid and urh.endDate=\"void\" and urh.superCommFk=u.userOid ";
				$superCommBeans = ManagerHelper::getBeans($sql, 'user');

				if($superCommBeans!=NULL && count($superCommBeans) > 3){
					$logErrorMsg = ManagerUtils::getLogErrorMsg("INCONSISTENCIA", get_class()."[".ManagerUtils::getCurrentMethod()."]", "Se ha recuperado más de un usuario relacionado con la comunidad con id=$userOid, nombre=$userBean->name");
					ManagerUtils::error($logErrorMsg);
					throw new Exception(Cte::$genericErrorMsg);
				}

				/* Dado que el tipo del usuario no es modificable, lo cargamos directamente sin tener que acceder a BD.*/
				$userTypeBean	= new HtmlSelectOptionBean(Cte::$COMM, Cte::$USER_TYPE_NAMES[Cte::$COMM]);
				$userTypeBeans	= array($userTypeBean);

				/* Recuperamos la lista de administradores para la select de filtro por administrador.*/
				$adminBeans		= ManagerHelper::getComboBeans($view="v_admins_combo", $superValue="");
				/* Recuperamos la lista de em1 para la select de filtro por em1.*/
				$em1Beans		= ManagerHelper::getComboBeans($view="v_em1_combo", $superValue="");
				/* Recuperamos los usuarios del sistema de tipo empresa de mantenimiento 2*/
				$em2Beans		= ManagerHelper::getComboBeans($view="v_em2_combo", $superValue="");
			}
			else{
				/* Si no es de tipo comunidad sólo necesitamos su información propiamente ya que no estará relacionado con otro usuario*/
				$sql 			= "select * from v_users where userOid=$userOid";
				$userBean		= ManagerHelper::getBean($sql, 'user');
				$userTypeBean	= new HtmlSelectOptionBean($userTypeId, Cte::$USER_TYPE_NAMES[$userTypeId]);
				$userTypeBeans	= array($userTypeBean);
				if($userTypeId == Cte::$EMP_MANT_2){
					/* Si es de tipo em2 deberemos recuperar dos listas: una con las comunidades que le pertenecen y otro con las que no.*/
					/* 1.- Lista de las comunidades que le pertenecen. */
					$sql 			= "select * from v_em2_comms_now where em2Oid=$userOid order by userName";
					$subCommBeans	= ManagerHelper::getBeans($sql, 'user');

					/* 2.- Lista de las comunidades no asociadas a la EM2. */
					$sql 			= "select * from v_comms where userOid not in (select userOid from v_em2_comms_now where em2Oid=$userOid) order by userName";
					$otherCommBeans	= ManagerHelper::getBeans($sql, 'user');
				}
			}
			$editUserBean	= new EditUserBean($userBean, $superCommBeans, $userTypeBeans, $adminBeans, $em1Beans, $em2Beans, $subCommBeans, $otherCommBeans);

			return $editUserBean;
		}

		/**
		 *
		 * Actualiza la base de datos con la nueva información del usuario.
		 *
		 * @param $userOid
		 * @param $usr
		 * @param $name
		 * @param $pwd
		 * @param $mail
		 * @param $tlf
		 * @param $userTypeFk
		 * @param $adminFk
		 * @param $em1Fk
		 * @param $em2Fk
		 * @param $oldAdminFk
		 * @param $oldEm1Fk
		 * @param $oldEm2Fk
		 *
		 * @return EditUserBean ($usr, $pwd, $name, $userTypeFk, $mail, $tlf, $userFk)
		 *
		 */
		public static function updateUser($userOid, $usr, $name, $pwd, $mail, $tlf, $fax, $envioAdicional, $userTypeFk, $adminFk, $em1Fk, $em2Fk, $oldAdminFk, $oldEm1Fk, $oldEm2Fk, $addTheseComms, $removeTheseComms){
			/* Guardamos en BD la información de la tabla users.*/
			updateDB::updateUser($userOid, $usr, md5($pwd), $mail, $tlf, $fax, $envioAdicional, $name, $userTypeFk);
			/* Si el usuario es de tipo comunidad y ha habido modificaciones en cuanto a su administrador o empresa de mantenimiento deberemos actualizar el histórico*/
			if($userTypeFk == Cte::$COMM){
				if($adminFk != $oldAdminFk){
					ManagerUtils::updateURH($userOid, $oldAdminFk, $adminFk);
				}
				if($em1Fk != $oldEm1Fk){
					ManagerUtils::updateURH($userOid, $oldEm1Fk, $em1Fk);
				}
				if($em2Fk != $oldEm2Fk){
					ManagerUtils::updateURH($userOid, $oldEm2Fk, $em2Fk);
				}
			}
			/* Si el usuario es de tipo EM2 trataremos las modificaciones que se hayan podido realizar en sus comunidades (tanto por que se hayan eliminado algunas como por que se le quieran asociar nuevas)*/
			else if($userTypeFk == Cte::$EMP_MANT_2){
				updateDB::updateSubComms($userOid, $addTheseComms, $removeTheseComms);
			}

		}

		/**
		 * Desactiva un usuario del sistema, lo que hace que no sea visible en la aplicación pero sin que se elimine del sistema
		 * por si se quiere recuperar.
		 *
		 * @param String $userOid
	 	 *
		 */
		public static function deleteUser($userOid){
			Validator::checkSuperUserInSession();
			$sql = "delete from users where userId=$userOid ";
			SQLHelper::executeStatement($sql);
		}

		/**
		 * Recupera la info necesaria para la vista con el formulario de subida de documentos al sistema y la devuelve encapsulada
		 * en un bean de presentación.
		 *
		 * @param $userFk
		 * @param $usr
		 *
		 * @return AddDocsBean
		 */
		public static function addDocs($userFk, $usr){
			$addDocsBean	= new AddDocsBean($userFk="", $usr="", session_id());
			$backUrl		= "";

			return $addDocsBean;
		}

		/**
		 * Guarda en base de datos y en el sistema de ficheros el documento a subir. Este action está preparado para ser llamadao
		 * via ajax.
		 *
		 * @param 		$docFile		Documento a subir. Su nombre es el codigo de agua al que se refiere.
		 * @param 		$docType		Tipo del documento.
		 *
		 * @return 		AddDocsBean
		 *
		 * @exception	Si no se encuentra ningún usuario relacionado con el codigo de agua.
		 *
		 */
		public static function insertDoc($docFile){
			/* En el nombre nos viene indicado el codigo de agua al cual se refiere el doc*/
			$fileName				= $docFile['name'];
			$pdfName				= ManagerUtils::getFileNameWithoutExtension($fileName);
			$docTypeWcAndDateArray	= explode("-", $pdfName);
			$docTypeOid				= $docTypeWcAndDateArray[0];
			/* En el caso de que el documento sea de tipo factura el id recuperado no será de codigo de agua sino de usuario (que puede ser comunidad, empresa de mantenimiento y posiblemente otros)*/
			$wcOid					= $docTypeWcAndDateArray[1];
			/* La fecha de recogida del documento la recuperamos del nombre del pdf*/
			$fecha_recogida			= $docTypeWcAndDateArray[2];
			$fecha_recogida			= str_replace("_", "/", $fecha_recogida);
			$fecha_recogida_time	= DateUtils::getTimeBySpanishDate($fecha_recogida);
			/* Machaco el valor anterior por que interesa coger la fecha de la subida y no la del doc*/
			$fecha_subida_time		= time();
			$docName				= $fileName;
			/* Valores de estado de envío del documento. Los ponemos todos como enviados y cambiamos tal y como vamos comprobando los superusuarios que tiene la comunidad.*/
			$sent_admin = $sent_em1 = $sent_em2 = $sent_comm = 1;
			if($docTypeOid == Cte::$FACTURA || $docTypeOid == Cte::$RECIBO){
				/* Hacemos esta asignación para que la semántica del nombre de la variable no sea confusa. Esto es por que en el caso de que el documento sea de tipo Factura o Recibo, la variable
				 * wcOid en realidad contiene el oid de la comunidad*/
				/* TODO: Entonces, si está claro que el oid se refiere a una comunidad no sé por que validamos que $userOid pueda ser de tipo admin o em1. Em1 seguro que puede ser pero admin también?*/
				$userOid			= $wcOid;
				/* Comprobamos el tipo de usuario al que pertenece el documento y marcamos el atributo de correspondiente a 'no enviado'*/
				if(Validator::isUserType($userOid, Cte::$COMM)){
					/* Hacemos esta asignación por motivos semánticos, ya que dentro de este if hemos comprobado que el identificador $userOid se refiere a una comunidad*/
					$commOid	= $userOid;
					if(ManagerUtils::hasAdmin($commOid)){
						if(ManagerUtils::hasEm1($commOid)){
							/* Si la comunidad tiene administrador y em1 el admin no debe ver los docs de tipo factura ni recibo, por lo que marcamos la coumna sent_admin a 1 y así nos aseguramos de que no se les envía.*/
							$sent_admin	= 1;
						}
						else{
							if(ManagerHelper::hasSuperUserMail($commOid, $superUserType=Cte::$ADMIN_STR)){
								/* Sólo lo marcamos como no enviado cuando tiene mail. En caso contrario lo mantenemos como no enviado, para evtar que si en un futuro se le asigna mail, se le envíen todos los docs anteriores.*/
								$sent_admin	= 0;
							}
						}
					}
					if(ManagerUtils::hasEm1($commOid)){
						if(ManagerHelper::hasSuperUserMail($commOid, $superUserType=Cte::$EM1_STR)){
							$sent_em1	= 0;
						}
					}
					/* Como las em2 nunca verán facturas ni recibos y aunque las vistas ya validan esto, marcamos el documenot como ya enviado a em2 y así tenemos una segunda validación de seguridad. */
					$sent_em2	= 1;
					if(ManagerHelper::hasUserMail($commOid)){
						/* Si la comunidad tiene asignada una cuenta de correo lo ponemos como no enviado*/
						$sent_comm	= 0;
					}
				}
				else if(ManagerHelper::hasUserMail($userOid)){
					if(Validator::isUserType($userOid, Cte::$ADMIN)){
						$sent_admin	= 0;
					}
					if(Validator::isUserType($userOid, Cte::$EMP_MANT_1)){
						$sent_em1	= 0;
					}
				}

				/* No comprobamos Em2 ya que las facturas y recibos nunca serán visibles para EM2.*/
				$sql 			= "select * from v_users where userOid=$userOid";
				$usr			= SQLHelper::getColumnValue($sql, $column='userUser', $required=true);
				if($usr == ""){
					$logErrorMsg = ManagerUtils::getLogErrorMsg("INCONSISTENCIA", get_class()."[".ManagerUtils::getCurrentMethod()."]", "Fichero $fileName de tipo factura. No se ha encontrado ningun usuario con el id $userOid");
					ManagerUtils::error($logErrorMsg);
					throw new Exception(Cte::$genericErrorMsg);
				}
				/* Dado que recibos y facturas no están ligadas a un wc detereminado pero que el modelo de datos nos obliga a relacionarlo
				 * con uno, recuperamos uno cualquier y lo usamos como clave foráneo del doc*/
				$wcOid			= ManagerUtils::getWaterCodeOidByUserOid($userOid);
				$dirToUpload 	= $_SESSION['dir_docsRoot']."$usr/facturas/";
			}
			else{
				$sql 			= "select * from v_comm_wcs where wcOid=$wcOid";
				$wcCommDao		= SQLHelper::getDao($sql);
				$usr			= $wcCommDao['commUser'];
				if($usr == ""){
					$logErrorMsg = ManagerUtils::getLogErrorMsg("INCONSISTENCIA", get_class()."[".ManagerUtils::getCurrentMethod()."]", "Fichero $fileName. No se ha encontrado ningun usuario relacionado con el watercode $wcOid");
					ManagerUtils::error($logErrorMsg);
					throw new Exception(Cte::$genericErrorMsg);
				}
				$dirToUpload 	= $_SESSION['dir_docsRoot']."$usr/$wcOid/";

				$commOid		= $wcCommDao['commOid'];
				if(ManagerUtils::hasAdmin($commOid)){
					if(ManagerHelper::hasSuperUserMail($commOid, $superUserType=Cte::$ADMIN_STR)){
						$sent_admin	= 0;
					}
				}
				if(ManagerUtils::hasEm1($commOid)){
					if(ManagerHelper::hasSuperUserMail($commOid, $superUserType=Cte::$EM1_STR)){
						$sent_em1	= 0;
					}
				}
				if(ManagerUtils::hasEm2($commOid)){
					if(ManagerHelper::hasSuperUserMail($commOid, $superUserType=Cte::$EM2_STR)){
						$sent_em2	= 0;
					}
				}
				if(ManagerHelper::hasUserMail($commOid)){
					/* Si la comunidad tiene asignada una cuenta de correo lo ponemos como no enviado*/
					$sent_comm	= 0;
				}
				//$usr			= SQLHelper::getColumnValue($sql, 'commUser', $required=true);
			}
			ManagerUtils::treatUserDocsHash($usr, $docFile);
			ManagerUtils::error("dirToUpload: *$dirToUpload*<br/>");
			ManagerUtils::managePdfToUpload($docFile, $dirToUpload, $docName);
			$sql = "insert into docs(docName, fileName, fecha_recogida, fecha_subida, wcFk, docTypeFk, sent_admin, sent_em1, sent_em2, sent_comm) values ('$docName', '$docName', '$fecha_recogida_time', '$fecha_subida_time', $wcOid, $docTypeOid, $sent_admin, $sent_em1, $sent_em2, $sent_comm) ";
			SQLHelper::executeStatement($sql);
			$return = array(
				'status' => '1',
				'name' => $docName
			);

			echo json_encode($return);
		}

		/**
		 * Gestiona el cierre de sesión de un usuario eliminando la info relativa a éste en $_SESSION.
		 *
		 */
		public static function closeSession(){
			unset($_SESSION['usr']);
			unset($_SESSION['userOid']);
			unset($_SESSION['name']);
			unset($_SESSION['userType']);
		}



		/**
		 * Elimina de la base de datos y del sistema de ficheros el fichero cuyo identificador se recibe por parámetro
		 *
		 * @param String $docId
		 * @param String $userFk
		 * @param String $searchDocType
		 * @param String $searchWcOid
		 * @param String $searchIniDate
		 * @param String $searchEndDate
		 * @param String $searchDocType
	 	 *
	 	 * @return showDocsBean
	 	 *
		 */
//		public static function deleteDoc($docId, $filePath, $pageNumber, $orderColumn, $orderType, $h1, $docsView, $commsComboView, $wcsComboView, $dtsComboFilters, $superWcsFk, $searchSuperCommColumn, $searchSuperCommFk, $searchCommFk, $searchAdminFk, $searchEm1Fk, $searchEm2Fk, $searchIniDate, $searchEndDate, $searchDocType, $searchWcOid, $itemsPerPage){
		public static function deleteDoc($docId, $filePath){
			$sql = "delete from docs where docId=$docId ";
			SQLHelper::executeStatement($sql);
			ManagerUtils::deleteFile($filePath);

			//$showDocsBean	= Manager::showDocs($pageNumber, $orderColumn, $orderType, $h1, $docsView, $commsComboView, $wcsComboView, $dtsComboFilters, $superWcsFk, $searchSuperCommColumn, $searchSuperCommFk, $searchCommFk, $searchAdminFk, $searchEm1Fk, @$searchEm2Fk, $searchIniDate, $searchEndDate, $searchDocType, $searchWcOid, $itemsPerPage);

			//return $showDocsBean;
		}

		public static function deleteDocs($docs){
			foreach($docs as $doc){
				$docId		= $doc['docId'];
				$filePath	= $doc['filePath'];
				$sql = "delete from docs where docId=$docId ";
				SQLHelper::executeStatement($sql);
				ManagerUtils::deleteFile($filePath);
			}
		}

		public static function printDocs($docs){
			/* TODO: Descomentar!!!! Me da error en desarrollo con el display_errors a E_ALL, pero puede que haga falta para que funcione */
			$pdf =& new PDF_AutoPrint();

			foreach($docs as $filePath){
				//$docId		= $doc['docId'];
				$pagecount = $pdf->setSourceFile($filePath);
				$tplidx = $pdf->importPage(1, '/MediaBox');
				$pdf->addPage();
				$pdf->useTemplate($tplidx, 10, 10, 200, 290);
			}
			$pdf->AutoPrint(true);
			$pdf->Output();

			//$showDocsBean	= Manager::showDocs($pageNumber, $orderColumn, $orderType, $userFk,
			//									$searchIniDate, $searchEndDate, $searchDocType, $searchWcOid, $_SESSION['itemsPerPage']);

			//return $showDocsBean;
		}


		/**
		 * Recupera los ficheros seleccionados por el usuario e inicia la descarga de todos ellos
		 *
		 * @param String $filesToDownload
	 	 *
		 */
		public static function downloadFiles($files){
			$zipfile = new PclZip('documentos.zip');
			$v_list = $zipfile->create($files, PCLZIP_OPT_REMOVE_ALL_PATH);
			if ($v_list == 0) {
				die ("Error: " . $zipfile->errorInfo(true));
			}

			header("Content-type: application/octet-stream");
			header("Content-disposition: attachment; filename=documentos.zip");
			header("Content-Type: application/force-download");

			readfile("documentos.zip");

		}

		/*************************************************************************
		 *
		 *
		 *
		 *
		 *
		 */
			/**
		 * Carga el formulario de alta de waterCode con las combos necesarias.
		 *
		 */
		public static function addWaterCode(){
		 	$commBeans 	= ManagerHelper::getComboBeans($view="v_comms_combo");

		    $editWaterCodeBean  = new EditWaterCodeBean(new EditWaterCodeBean(), $commBeans);

		    return $editWaterCodeBean;
		}


		/**
		 * Guarda en bd la información del objeto waterCode introduciad por el usuario.
		 *
		 * @param $wcOid    - Number
		 * @param $wcName   - String
		 * @param $comm - String
		 */
		public static function insertWaterCode($wcOid, $wcName, $comm){
		    $errorsMsg  = "";
		    $errorsMsg  .= Validator::validateUnique_insert($objectLabel="Código de agua", $tableName='watercodes', $columnName='wcName', $value=$wcName, $fieldLabel='Nombre', $type='String');
		    if($errorsMsg != ""){
		        throw new Exception($errorsMsg);
		    }
		    $sql = "insert into watercodes (wcOid,  wcName,  userFk ) values ($wcOid,  '$wcName',  $comm )";
		    ManagerUtils::executeStatement($sql);

		    $wcOid              = ManagerUtils::getLastObjectIdByPkColumn('watercodes', 'wcOid');
		    $editWaterCodeBean  = Manager::editWaterCode($wcOid);

		    return $editWaterCodeBean;
		}

		/**
		 * Carga el formulario de edición de waterCode con la información del objeto identificado por $wcOid.
		 *
		 * @param $wcOid    - Number
		 * @param $wcName   - String
		 * @param $comm - String
		 */
		public static function editWaterCode($wcOid){
		    $sql			= "select * from watercodes where wcOid=$wcOid";
		    $waterCodeBean	= ManagerHelper::getBean($sql, 'waterCodeEdit');
			$commBeans		= ManagerHelper::getComboBeans('v_comms_combo');

		    $editWaterCodeBean  = new EditWaterCodeBean($waterCodeBean, $commBeans);

		    return $editWaterCodeBean;
		}

		/**
		 * Actualiza en base de datos la información relativa al objeto de tipo waterCode identificado por $wcOid.
		 *
		 * @param $wcOid    - Number
		 * @param $wcName   - String
		 * @param $comm - String
		 */
		public static function updateWaterCode($wcOid, $wcName, $comm){
		    $sql				= "update watercodes set wcName='$wcName', userFk=$comm where wcOid=$wcOid";
			SQLHelper::executeStatement($sql);
		    $editWaterCodeBean  = Manager::editWaterCode($wcOid);

		    return $editWaterCodeBean;
		}

		/**
		 * Muestra el listado de objetos de tipo waterCode, aplicando la paginación y filtros recibidos..
		 *
		 * @param $pageNumber   - Number - Indica el número de la página a cargar
		 * @param $orderColumn  - string - Indica la columna por la que se va a ordenar el listado
		 * @param $orderType    - string - Indica si el orden es ascendente ('asc ') o descendente ('desc ')
		 * @param $searchWcOid  - Number
		 * @param $searchWcName - String
		 * @param $searchComm   - String
		 */
		public static function showWaterCodes($orderColumn, $orderType, $pageNumber , $searchWcOid,  $searchWcName,  $searchComm, $itemsPerPage, $ajax){
		    $comm_comboBeans		= ManagerHelper::getComboBeans($view='v_comms_combo');

			$sql					= ManagerUtils::getShowWaterCodesSql($searchWcOid,  $searchWcName,  $searchComm );
			$fullWcDaosList	 		= SQLHelper::getPageList($sql, $orderColumn, $orderType, $pageNumber, 'waterCodeList');
			$fullWaterCodeBeansList	= P2P::fullDaoList($fullWcDaosList);

		    $showWaterCodesBean 	= new ShowWaterCodesBean($searchWcOid,  $searchWcName,  $searchComm , $comm_comboBeans, $ajax, $fullWaterCodeBeansList);

		    return $showWaterCodesBean;
		}

		/**
		 * Envía a la papelera de reciclaje el objeto de tipo 'waterCode' identificado por $wcOid.
		 *
		 * @param $wcOid    - Number
		 */
		public static function recBinWaterCode($wcOid){
		    /* No se borra el registro sino que se pone como inactivo*/
		    deleteDB::recBinWaterCode($wcOid);
		}

		/**
		 * Elimina definitivamente del sistema el objeto de tipo 'waterCode' identificado por $wcOid
		 *
		 * @param $wcOid    - Number
		 */
		public static function deleteWaterCode($wcOid){
		    /* Elimina el registro del sistema.*/
		    deleteDB::deleteWaterCode($wcOid);
		}

		/**
		 * Envía a la papelera de reciclaje los objetos identificados por la lista de wcOids recibidos por parámetro.
		 *
		 *  * @param $wcOid - Number
		 */
		public static function recBinMassiveWaterCodes($selectedWcOids){
		    /* Recorremos el listado de identificadores recibidos para enviarlos a la papelera uno a uno.*/
		    foreach($selectedWcOids as $wcOid){
		        Manager::recBinWaterCode($wcOid);
		    }
		}

		/**
		 * Elimina definitivamente los objetos identificados por la lista de wcOids recibidos por parámetro.
		 *
		 * @param wcOids    - Number    -
		 */
		public static function deleteMassiveWaterCodes($selectedWcOids){
		    /* Recorremos el listado de identificadores recibidos para enviarlos a la papelera uno a uno.*/
		    foreach($selectedWcOids as $wcOid){
		        Manager::deleteWaterCode($wcOid);
		    }
		}

		/**
		 * Recupera los documentos del usuario en sesión y aplicando los filtros correspondientes. Según el tipo de usuario que hay en sesión devolverá un conjunto de docs u otros, ya que no todos los tipos de usuario tienen
		 * acceso a los mismos docs. Esta lógica viene definida por las diferentes vistas que hay en sesión por lo que por programación lo único que se necesita es seleccionar a qué vistas atacar. Esto ya se ha determinado en
		 * la capa input, por lo que aquí nos llega parametrizado y se pueden hacer llamadas genéricas a la capa de bd.
		 *
		 *
		 * @param $pageNumber				Página del listado a mostrar
		 * @param $orderColumn				Columna por la que ordenar
		 * @param $orderType				Orden ascendente o descendente
		 * @param $h1						Título a mostrar. Incluye el usuario del que se muestran los docs.
		 * @param $docsView					Vista a la que atacar para recuperar los docs (esto se ha determinado en input en función del tipo de usuario en sesión)
		 * @param $commsComboView			Vista a la que atacar para recuperar las comunidades que irán en la lista desplegable para filtrar el listado de docs. No aplica cuando el usuario en sesión es comunidad.
		 * @param $wcsComboView				Vista a la que atacar para recuperar los códigos de agua que irán en la lista desplegable para filtrar el listado de docs.
		 * @param $superWcsFk				Valor por el que filtrar la lista desplegable con la que filtrar por código de agua.
		 * @param $searchSuperCommColumn	Columna de la vista por la que filtrar el usuario al que pertenece una comunidad. Según la vista de docs esta columna se llamará em1Oid o adminOid, en este parámetro nos viene definido.
		 * @param $searchSuperCommFk		Valor contra el que comparar la columna $searchSuperCommColumn
		 * @param $searchCommFk				Filtro por comunidad
		 * @param $searchIniDate			Filtro por fecha de inicio
		 * @param $searchEndDate			Filtro por fecha de fin
		 * @param $searchDocType			Filtro por tipo de documento
		 * @param $searchWcOid				Filtro por código de agua
		 * @param $itemsPerPage				Número de elementos por página a mostrar en el listado
		 * @return unknown_type
		 */
		public static function showDocs($pageNumber, $orderColumn, $orderType, $h1, $docsView, $commsComboView, $wcsComboView, $dtsComboFilters, $superWcsFk, $searchSuperCommColumn, $searchSuperCommFk, $searchCommFk, $searchAdminFk, $searchEm1Fk, $searchEm2Fk, $searchIniDate, $searchEndDate, $searchDocType, $searchWcOid, $itemsPerPage){
			/* Inicializamos los arrays de comboBeans que no se crean en todos los casos (para evitar valor NULL)*/
			$commBeans			= array();
			$adminBeans			= array();
			$em1Beans			= array();
			$em2Beans			= array();
			$sql				= ManagerUtils::getDocsSql($docsView, $searchSuperCommColumn, $searchSuperCommFk, $searchCommFk, $searchIniDate, $searchEndDate, $searchDocType, $searchWcOid);
			$fullDocDaosList 	= SQLHelper::getPageList($sql, $orderColumn, $orderType, $pageNumber, 'doc');
			$fullDocsBeansList	= P2P::fullDaoList($fullDocDaosList);

			//añadido para filtrar bien el listado
			foreach ($fullDocsBeansList->itemBeans as $key => $docBean) {
				
					$sql =  "SELECT * FROM docs 
						inner join watercodes on watercodes.wcOid = docs.wcFk 
						inner join usersrelhistory on usersrelhistory.commFk = watercodes.userFk 
						where docs.docId = ".$docBean->docId." and usersrelhistory.endDate = 'void'";

					//si viene más de un resultado cojo aquel que pertenezca a una comunidad y no a una em1
					$docs = SQLHelper::executeListQuery($sql);
					while($row = mysql_fetch_array($docs)){
	    				$sql1 =  "SELECT * FROM users 
						where users.userId = ".$row['superCommFk']." ";

						$res = SQLHelper::executeListQuery($sql1);
						if($res['userTypeFk'] == 2){
							$fullDocsBeansList[$key]->wcName=$res['name'];
							break;
						}
	    			}
    			
			}

			/* Pequeña situación patológica que consiste en que la columna "Nombre" del listado no siempre contiene el mismo campo de bd por lo que no se puede ordenar por sql. En P2P se informa un wcName o CommName,
			 * en función del tipo de documento y aquí pasarmos a ordenar por esta propiedad del bean si el orderColumn es wcName. */
			if($orderColumn == "wcName"){
				$docBeans		= $fullDocsBeansList->itemBeans;
				if($orderType=="asc "){
					usort($docBeans, array("DocBean", "cmpByWcNameAsc"));
				}
				else if($orderType=="desc "){
					usort($docBeans, array("DocBean", "cmpByWcNameDesc"));
				}
				$fullDocsBeansList->itemBeans=$docBeans;
			}
			if(!Validator::isCommInSession()){
				/* Recuperamos la lista de comunidades diciéndole de qué vista las ha de recuperar y el filtro a aplicar.*/
				$commBeans			= ManagerHelper::getComboBeans($commsComboView, $searchSuperCommFk);
			}
			if(Validator::isSuperUserInSession()){
				/* Recuperamos la lista de administradores para la select de filtro por administrador.*/
				$adminBeans		= ManagerHelper::getComboBeans($view="v_admins_combo", $superValue="");
				/* Recuperamos la lista de em1 para la select de filtro por em1.*/
				$em1Beans		= ManagerHelper::getComboBeans($view="v_em1_combo", $superValue="");
				$em2Beans		= ManagerHelper::getComboBeans($view="v_em2_combo", $superValue="");
			}

			/* El parámetro dtsDocsFilters incluirá, si el usuario en sesión es una comunidad, los filtros necesarios para que en la combo de tipos de documentos no aparezcan recibos ni facturas (viene de input).*/
			$docTypeBeans		= ManagerHelper::getComboBeans($view="v_dts_combo", $superValue="", $dtsComboFilters);

			$wcBeans			= ManagerHelper::getComboBeans($wcsComboView, $superWcsFk);

			$showDocsBean		= new ShowDocsBean(	@$usr, @$userName, $fullDocsBeansList, $searchCommFk, $searchAdminFk, $searchEm1Fk, @$searchEm2Fk, $searchIniDate, $searchEndDate, $searchDocType, $searchWcOid,
														$docTypeBeans, $wcBeans, $commBeans, $adminBeans, $em1Beans, $em2Beans, $submitAction="showDocs", $h1);
			return $showDocsBean;
		}

public static function sendUserDocs(){
			Manager::sendCommNoAdminDocs();
			Manager::sendDocs('admin');
			Manager::sendDocsEM1();
			Manager::sendDocsEM2();
			Manager::sendCommDocs();
			//Manager::sendDocs('em1');
			//Manager::sendDocs('em2');
		}

		/* Método genérico que sirve para el envío de mails a los 3 tipos de usuarios. La idea es */
		public static function sendCommDocs(){
			try{
		        Validator::checkSuperUserInSession();
		        /* Tipo de usuario con la inicial en mayúsculas que nos hará falta en algunos casos*/
		        //$userType_firstMayus	= ucfirst($userType);
				$sql = "select * from v_comm_docs_send";

				//Tengo que quitar del array los que tienen asociada una em1 pues funcionan de manera diferente
				$docDaos = SQLHelper::executeListQuery($sql);

				$rows = array();
    			while($row = mysql_fetch_array($docDaos)){
    				$rows[] = $row;
    			}

				foreach($rows as $key => $row){
					$sqlP = "SELECT * FROM usersrelhistory 
					inner join users on usersrelhistory.superCommFk = users.userId 
					where usersrelhistory.commFk = ".$row['commOid']." and users.userTypeFk = '3' and usersrelhistory.endDate = 'void' ";

					if(SQLHelper::getDaos($sqlP) != NULL){
						unset($rows[$key]);
					}
				}
				$rows = array_values($rows);

				if($rows != NULL){
					$docs			= array();		/* Documentos a enviar por correo.*/

					/* El mail es necesario a la hora de enviar el correo con los docs.*/
					$commMail		= "";			/* Mail al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$commMailAux	= "";			/* Mail del administrador al que pertenece el doc que tratamos en cada iteración. En el momento en el que sea diferente a $adminMail hay que enviar los docs a este último.*/

					/* El nombre de usuario de la comunidad es necesario a la hora de construir la ruta al documento.*/
					$commUser		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$commUserAux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					/* Variable donde almacenamos si ha habido un error en el envío.*/
					$error			= false;

					/* El nombre del administrador y el de la comunidad se utilizan para informar el log.*/
					$commName		= "";
					$commNameAux	= "";

					/* Campo donde se guardan los mails a los que hay que enviar también el zip con los docs.*/
					$envio_adicional		= "";			/* Mails adicionales asociados a la comunidad que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$envio_adicional_aux	= "";			/* Mails adicionales de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					$whereClauseArray	= array();	/* Esto se utiliza para el update que setea sent=1 en los documentos recién enviados.*/

					$pendiente		= false;		/* Como los correos se envían cuando se detecta que la comunidad actual no es igual a la tratada en la iteración anterior, hay que dar un tratamiento especial a la última comunidad.*/

					$date			= date("d_m_Y");
					$dirName		= "AQUALAB_$date";
					if(!file_exists($dirName)){
						$dir			= mkDir($dirName);
					}
					$i				= 0;
					$ddf 			= fopen($_SESSION['sentCommsLog'],'w');	/* Abro el ficheor de log con los envíos.*/
					fwrite($ddf, "Dentro<br/>");

					foreach($rows as $docDao){
						$docOid			= $docDao['docOid'];
						$commUserAux	= $docDao['commUser'];
						$commMailAux	= $docDao['commMail'];
						$docTypeOid		= $docDao['docTypeOid'];
						$docTypeName	= ManagerUtils::stripAccents(utf8_encode($docDao['docTypeName'])); /* Se utiliza para contrsuir el nombre del documento.*/
						$wcOid			= $docDao['wcOid'];
						$envio_adicional_aux= $docDao['envio_adicional'];
						$docName		= $docDao['docName'];
						$encodedWcName	= utf8_encode($docDao['wcName']);
						$commNameAux	= utf8_encode($docDao['commName']);
						$newDocName		= ManagerUtils::getFileNameFromDao($docDao); 		/* Nombre del fichero tal y como se va a crear. */
						$docPath		= ManagerUtils::getFilePath($docTypeOid, $commUserAux, $docName, $wcOid, $docOid, $sendToUserType="Comm");

						if($commUser == ""){
							/* Añadimos a la clausula where (que se usa para marcar los documentos que han sido enviados) el documento que tratamos en esta iteración.*/
							$whereClauseArray[]	= "docId=$docOid";
							$commName	= utf8_encode($docDao['commName']);
							$commMail	= $docDao['commMail'];
							$commUser	= $docDao['commUser'];
							if($commMail != "" && $commMail != "-"){
								fwrite($ddf, "<font color='black'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								/* En este if sólo entramos en la primera iteración. */
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								$pendiente = true;
							}
						}
						else if($commUser != $commUserAux){
							$zipName	= $dirName.".zip";
							$zip 		= new PclZip($dirName.".zip");
							$docs[0]	= $dirName;
							$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
							if ($v_list == 0) {
								die ("Error: " . $doc->errorInfo(true));
							}
							$doc	= $_SESSION['dir_root'].$zipName;
							if($commMail != "" && $commMail != "-"){
								try{
									$asunto	= "Envio a la comunidad $commMail.";
									ManagerUtils::sendUserDocs($commMail, $doc, $envio_adicional, $asunto,$docTypeOid);
								}catch(Exception $e){
									fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$commMail</strong> del super <strong>".ManagerUtils::stripAccents($commName)."</strong>.<br/><br/>");
									$error=true;
								}
								if($error != true){
									ManagerUtils::setSentDocs($column="sent_comm", $whereClauseArray);
									ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
									fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$commMail</strong> de <strong>".ManagerUtils::stripAccents($commName)."</strong></font><br/><br/><br/>");
								}
								else{
									$error = false;

								}
							}
							else{
								fwrite($ddf, "<font color='red'>El usuario <strong>".ManagerUtils::stripAccents($commName)."</strong> no tiene asociada ninguna cuenta de correo.<br/><br/>");
							}
							$commName			= $commNameAux;
							$commUser			= $commUserAux;
							$commMail			= $commMailAux;
							$envio_adicional	= $envio_adicional_aux;
							if($commMail != ""  && $commMail != "-"){
								$i++;
								if($i%2==0){
									$color	= "black";
								}
								else{
									$color	= "green";
								}
								fwrite($ddf, "<font color='$color'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");

								if(file_exists($dirName)){
									ManagerUtils::deleteDir($dirName);
								}

								$date			= date("d_m_Y");
								if(!file_exists($dirName)){
									$dir			= mkDir($dirName);
								}
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
							}
							$whereClauseArray = array();
							$whereClauseArray[]	= "docId=$docOid";
						}
						else{
							if($commMailAux != ""  && $commMailAux != "-"){
								$whereClauseArray[]	= "docId=$docOid";
								/* En este if comprobamos que seguimos tratando los documentos del mismo usuario, por lo que añadimos el documento al zip y continuamos.*/
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commNameAux)."</strong><br/><br/>");
							}
						}
					}
					if(($i>0 || $pendiente) && $commMail != ""  && $commMail != "-"){
						$zipName	= $dirName.".zip";
						$zip 		= new PclZip($dirName.".zip");
						$docs[0]	= $dirName;
						$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
						if ($v_list == 0) {
							die ("Error: " . $doc->errorInfo(true));
						}
						$doc	= $_SESSION['dir_root'].$zipName;
						try{
							$asunto	= "Enviado a la comunidad $commMail.";
							ManagerUtils::sendUserDocs($commMail, $doc, $envio_adicional, $asunto,$docTypeOid);
						}catch(Exception $e){
							fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$commMail</strong> de <strong>".ManagerUtils::stripAccents($commName)." Error: ".$e."</strong>.<br/><br/>");
							$error=true;
						}
						if($error != true){
							ManagerUtils::setSentDocs($column="sent_comm", $whereClauseArray);
							ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
							fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$commMail</strong> <strong>".ManagerUtils::stripAccents($commName)."</strong></font><br/><br/><br/>");
						}
						else{
							$error = false;
						}
						//fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$adminMail</strong> del Administrador: <strong>".ManagerUtils::stripAccents($adminName)."</strong></font><br/><br/><br/>");
						if(file_exists($dirName)){
							ManagerUtils::deleteDir($dirName);
						}
						if(!file_exists($dirName)){
							$dir			= mkDir($dirName);
						}
						if(file_exists($zipName)){
							ManagerUtils::deleteFile($zipName);
						}

					}

					fwrite($ddf, "<font color='green'>El envio se ha realizado correctamente</font>");
					fclose($ddf); /* Cerramos el fichero de log*/
				}
				else{
					fwrite($ddf, "<font color='red'>No hay documentos a enviar</font>");
				}
			}catch(Exception $e){
				fwrite($ddf, "<font color='red'>Error en el env&iacute;o</font>");
			}
			if(file_exists(@$dirName)){
				ManagerUtils::deleteDir($dirName);
			}
			if(file_exists(@$zipName)){
				ManagerUtils::deleteFile($zipName);
			}

		}

/* Método genérico que sirve para el envío de facturas a las comunidades sin administrador asociado */
		public static function sendCommNoAdminDocs(){
			try{
		        Validator::checkSuperUserInSession();
				$sql = 	"select docs.docId, docs.docName, docs.docTypeFk, 
						doctypes.docTypeName,
						watercodes.userFk, watercodes.wcName, watercodes.wcOid,
						users.name, users.userId, users.usr, users.mail, users.envio_adicional, 
						usersrelhistory.commFk, usersrelhistory.superCommFk
						FROM docs
						INNER JOIN doctypes ON docs.docTypeFk = doctypes.docTypeOid
						INNER JOIN watercodes ON docs.wcFk = watercodes.wcOid 
						INNER JOIN users ON watercodes.userFk = users.userId 
						INNER JOIN usersrelhistory ON users.userId = usersrelhistory.commFk
						where docs.docTypeFk = '1' and docs.sent_comm = '0' and usersrelhistory.superCommFk = '-138' and usersrelhistory.endDate='void'";

				$docDaos	= SQLHelper::executeListQuery($sql);

				//Recorremos los documentos y comprobamos si tiene asociada una em1 y lo quito del array.
				$rows = array();
    			while($row = mysql_fetch_array($docDaos)){
    				$rows[] = $row;
    			}
				foreach($rows as $key => $row){
					$sqlP = "SELECT * FROM usersrelhistory 
					inner join users on usersrelhistory.superCommFk = users.userId 
					where usersrelhistory.commFk = ".$row['userId']." and users.userTypeFk = '3' and usersrelhistory.endDate = 'void' ";

					if(SQLHelper::getDaos($sqlP) != NULL){
						unset($rows[$key]);
					}
				}
				$rows = array_values($rows);


				if($rows != NULL){
					$docs			= array();		/* Documentos a enviar por correo.*/

					/* El mail es necesario a la hora de enviar el correo con los docs.*/
					$commMail		= "";			/* Mail al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$commMailAux	= "";			/* Mail del administrador al que pertenece el doc que tratamos en cada iteración. En el momento en el que sea diferente a $adminMail hay que enviar los docs a este último.*/

					/* El nombre de usuario de la comunidad es necesario a la hora de construir la ruta al documento.*/
					$commUser		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$commUserAux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					/* Variable donde almacenamos si ha habido un error en el envío.*/
					$error			= false;

					/* El nombre del administrador y el de la comunidad se utilizan para informar el log.*/
					$commName		= "";
					$commNameAux	= "";

					/* Campo donde se guardan los mails a los que hay que enviar también el zip con los docs.*/
					$envio_adicional		= "";			/* Mails adicionales asociados a la comunidad que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$envio_adicional_aux	= "";			/* Mails adicionales de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					$whereClauseArray	= array();	/* Esto se utiliza para el update que setea sent=1 en los documentos recién enviados.*/

					$pendiente		= false;		/* Como los correos se envían cuando se detecta que la comunidad actual no es igual a la tratada en la iteración anterior, hay que dar un tratamiento especial a la última comunidad.*/

					$date			= date("d_m_Y");
					$dirName		= "AQUALAB_$date";
					if(!file_exists($dirName)){
						$dir			= mkDir($dirName);
					}
					$i				= 0;
					$ddf 			= fopen($_SESSION['sentCommsNoAdminLog'],'w');	/* Abro el fichero de log con los envíos.*/
					fwrite($ddf, "Dentro<br/>");
					foreach($rows as $docDao){
						$docOid			= $docDao['docId']; //idDoc
						$commUserAux	= $docDao['usr']; //name usuario
						$commMailAux	= $docDao['mail'];
						$docTypeOid		= $docDao['docTypeFk']; //id tipo doc
						$docTypeName	= ManagerUtils::stripAccents(utf8_encode($docDao['docTypeName'])); //nombre tipo doc
						$wcOid			= $docDao['wcOid']; //watercode id
						$envio_adicional_aux= $docDao['envio_adicional'];
						$docName		= $docDao['docName']; //nombre doc
						$encodedWcName	= utf8_encode($docDao['wcName']); //watercode name
						$commNameAux	= utf8_encode($docDao['name']); //com name
						$newDocName		= ManagerUtils::getFileNameFromDao2($docDao); 		/* Nombre del fichero tal y como se va a crear. */
						$docPath		= ManagerUtils::getFilePath($docTypeOid, $commUserAux, $docName, $wcOid, $docOid, $sendToUserType="Comm");

						if($commUser == ""){
							/* Añadimos a la clausula where (que se usa para marcar los documentos que han sido enviados) el documento que tratamos en esta iteración.*/
							$whereClauseArray[]	= "docId=$docOid";
							$commName	= utf8_encode($docDao['name']);
							$commMail	= $docDao['mail'];
							$commUser	= $docDao['usr'];
							if($commMail != "" && $commMail != "-"){
								fwrite($ddf, "<font color='black'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								/* En este if sólo entramos en la primera iteración. */
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								$pendiente = true;
							}
						}
						else if($commUser != $commUserAux){
							$zipName	= $dirName.".zip";
							$zip 		= new PclZip($dirName.".zip");
							$docs[0]	= $dirName;
							$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
							if ($v_list == 0) {
								die ("Error: " . $doc->errorInfo(true));
							}
							$doc	= $_SESSION['dir_root'].$zipName;
							if($commMail != "" && $commMail != "-"){
								try{
									$asunto	= "Enviado a la comunidad $commMail.";
									ManagerUtils::sendUserDocs($commMail, $doc, $envio_adicional, $asunto,$docTypeOid);
								}catch(Exception $e){
									fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$commMail</strong> del super <strong>".ManagerUtils::stripAccents($commName)."</strong>.<br/><br/>");
									$error=true;
								}
								if($error != true){
									ManagerUtils::setSentDocs($column="sent_comm", $whereClauseArray);
									ManagerUtils::setSentDocs($column="sent_admin", $whereClauseArray);
									ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
									fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$commMail</strong> de <strong>".ManagerUtils::stripAccents($commName)."</strong></font><br/><br/><br/>");
								}
								else{
									$error = false;

								}
							}
							else{
								fwrite($ddf, "<font color='red'>El usuario <strong>".ManagerUtils::stripAccents($commName)."</strong> no tiene asociada ninguna cuenta de correo.<br/><br/>");
							}
							$commName			= $commNameAux;
							$commUser			= $commUserAux;
							$commMail			= $commMailAux;
							$envio_adicional	= $envio_adicional_aux;
							if($commMail != "" && $commMail != "-"){
								$i++;
								if($i%2==0){
									$color	= "black";
								}
								else{
									$color	= "green";
								}
								fwrite($ddf, "<font color='$color'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");

								if(file_exists($dirName)){
									ManagerUtils::deleteDir($dirName);
								}

								$date			= date("d_m_Y");
								if(!file_exists($dirName)){
									$dir			= mkDir($dirName);
								}
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
							}
							$whereClauseArray = array();
							$whereClauseArray[]	= "docId=$docOid";
						}
						else{
							if($commMailAux != "" && $commMailAux != "-"){
								$whereClauseArray[]	= "docId=$docOid";
								/* En este if comprobamos que seguimos tratando los documentos del mismo usuario, por lo que añadimos el documento al zip y continuamos.*/
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commNameAux)."</strong><br/><br/>");
							}
						}
					}
					if(($i>0 || $pendiente) && $commMail != "" && $commMail != "-"){
						$zipName	= $dirName.".zip";
						$zip 		= new PclZip($dirName.".zip");
						$docs[0]	= $dirName;
						$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
						if ($v_list == 0) {
							die ("Error: " . $doc->errorInfo(true));
						}
						$doc	= $_SESSION['dir_root'].$zipName;
						try{
							$asunto	= "Enviado a la comunidad $commMail.";
							ManagerUtils::sendUserDocs($commMail, $doc, $envio_adicional, $asunto,$docTypeOid);
						}catch(Exception $e){
							fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$commMail</strong> de <strong>".ManagerUtils::stripAccents($commName)."</strong>.<br/><br/>");
							$error=true;
						}
						if($error != true){
							ManagerUtils::setSentDocs($column="sent_comm", $whereClauseArray);
							ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
							ManagerUtils::setSentDocs($column="sent_admin", $whereClauseArray);
							fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$commMail</strong> <strong>".ManagerUtils::stripAccents($commName)."</strong></font><br/><br/><br/>");
						}
						else{
							$error = false;
						}
						//fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$adminMail</strong> del Administrador: <strong>".ManagerUtils::stripAccents($adminName)."</strong></font><br/><br/><br/>");
						if(file_exists($dirName)){
							ManagerUtils::deleteDir($dirName);
						}
						if(!file_exists($dirName)){
							$dir			= mkDir($dirName);
						}
						if(file_exists($zipName)){
							ManagerUtils::deleteFile($zipName);
						}

					}

					fwrite($ddf, "<font color='green'>El envio se ha realizado correctamente</font>");
					fclose($ddf); /* Cerramos el fichero de log*/
				}
				else{
					fwrite($ddf, "<font color='red'>No hay documentos a enviar</font>");
				}
			}catch(Exception $e){
				fwrite($ddf, "<font color='red'>Error en el env&iacute;o</font>");
			}
			if(file_exists(@$dirName)){
				ManagerUtils::deleteDir($dirName);
			}
			if(file_exists(@$zipName)){
				ManagerUtils::deleteFile($zipName);
			}

		}


		
		/* Método genérico que sirve para el envío de mails a los 3 tipos de usuarios. (actualmente solo a los admin) */
		public static function sendDocs($userType){
			try{
		        Validator::checkSuperUserInSession();
		        /* Tipo de usuario con la inicial en mayúsculas que nos hará falta en algunos casos*/
		        $userType_firstMayus	= ucfirst($userType);
				$sql = "select * from v_".$userType."_docs_send";
				$docDaos	= SQLHelper::executeListQuery($sql);

				//Recorremos los documentos y comprobamos si tiene asociada una em1, y si es de tipo factura lo quito del array.
				while($row = mysql_fetch_array($docDaos)){
    				$rows[] = $row;
    			}
    			/*print '<pre>';
    			print_r($rows);
    			print '</pre>';
    			exit;*/
				foreach($rows as $key => $row){
					$sqlP = "SELECT * FROM usersrelhistory 
					inner join users on usersrelhistory.superCommFk = users.userId 
					where usersrelhistory.commFk = ".$row['commOid']." and users.userTypeFk = '3' and usersrelhistory.endDate = 'void' ";
					//print $sqlP;
					if((SQLHelper::getDaos($sqlP) != NULL) && ($row['docTypeOid'] == '1' or $row['docTypeOid'] == '101')){
						unset($rows[$key]);
					}
				}
				$rows = array_values($rows);



				if($rows != NULL){
					$docs			= array();		/* Documentos a enviar por correo.*/

					/* El mail es necesario a la hora de enviar el correo con los docs.*/
					$superMail		= "";			/* Mail al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superMailAux	= "";			/* Mail del administrador al que pertenece el doc que tratamos en cada iteración. En el momento en el que sea diferente a $adminMail hay que enviar los docs a este último.*/

					/* El nombre de usuario de la comunidad es necesario a la hora de construir la ruta al documento.*/
					$commUser		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$commUserAux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					/* Variable donde almacenamos si ha habido un error en el envío.*/
					$error			= false;

					/* El nombre del administrador y el de la comunidad se utilizan para informar el log.*/
					$superName		= "";
					$commName		= "";
					$superNameAux	= "";
					$commNameAux	= "";

					/* NOTA: De momento es temporal, para saber con qué administrador se trabaja en cada momento.*/
					$superUser		= "";			/* Username	del administrador al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superUserAux	= "";			/* Username del administrador al que pertenece el doc que tratamos en cada iteración.*/

					/* Campo donde se guardan los mails a los que hay que enviar también el zip con los docs.*/
					$envio_adicional		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$envio_adicional_aux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					$whereClauseArray	= array();	/* Esto se utiliza para el update que setea sent=1 en los documentos recién enviados.*/

					$pendiente		= false;		/* Como los correos se envían cuando se detecta que el administrador actual no es igual al tratado en la iteración anterior, hay que dar un tratamiento especial al último administrador.*/

					$date			= date("d_m_Y");
					$dirName		= "AQUALAB_$date";

					if(!file_exists($dirName)){
						$dir			= mkDir($dirName);
					}
					//print $dir.' '.$dirName;
					$i				= 0;
					$ddf 			= fopen($_SESSION['sent'.$userType_firstMayus.'Log'],'w');	/* Abro el ficheor de log con los envíos.*/
					foreach($rows as $docDao){
						$docOid			= $docDao['docOid'];
						$superUserAux	= $docDao[$userType.'User'];
						$superMailAux	= $docDao[$userType.'Mail'];
						$commUserAux	= $docDao['commUser'];
						$docTypeOid		= $docDao['docTypeOid'];
						$docTypeName	= ManagerUtils::stripAccents(utf8_encode($docDao['docTypeName'])); /* Se utiliza para contrsuir el nombre del documento.*/
						$wcOid			= $docDao['wcOid'];
						$envio_adicional_aux= $docDao['envio_adicional'];
						$docName		= $docDao['docName'];
						$encodedWcName	= utf8_encode($docDao['wcName']);
						$commNameAux	= utf8_encode($docDao['commName']);
						$superNameAux	= utf8_encode($docDao[$userType.'Name']);
						$newDocName		= ManagerUtils::getFileNameFromDao($docDao); 		/* Nombre del fichero tal y como se va a crear. */
						$docPath		= ManagerUtils::getFilePath($docTypeOid, $commUserAux, $docName, $wcOid, $docOid, $sendToUserType=$userType);

						if($superUser == ""){
							/* Añadimos a la clausula where (que se usa para marcar los documentos que han sido enviados) el documento que tratamos en esta iteración.*/
							$whereClauseArray[]	= "docId=$docOid";
							$superName	= utf8_encode($docDao[$userType.'Name']);
							$commName	= utf8_encode($docDao['commName']);
							$superMail	= $docDao[$userType.'Mail'];
							$superUser	= $docDao[$userType.'User'];
							$envio_adicional = $docDao['envio_adicional'];
							if($superMail != "" && $superMail != "-"){
								fwrite($ddf, "<font color='black'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								/* En este if sólo entramos en la primera iteración. */
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								$pendiente = true;
							}
						}
						else if($superUser != $superUserAux){
							$zipName	= $dirName.".zip";
							$zip 		= new PclZip($dirName.".zip");
							$docs[0]	= $dirName;
							$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
							if ($v_list == 0) {
								die ("Error: " . $doc->errorInfo(true));
							}

							$doc	= $_SESSION['dir_root'].$zipName;
							if($superMail != "" && $superMail != "-"){
								try{
									/* Este es el anterior (INICIO)*/
									//ManagerUtils::sendUserDocs($superMail, $doc);
									/* Este es el anterior (FIN)*/
									if($userType=='admin'){
										$asunto	= "Envio al administrador $superMail";
									}
									else if($userType=='em1'){
										$asunto	= "Envio a la Empresa de mantenimiento $superMail";
									}
									else if($userType=='em2'){
										$asunto	= "Envio a la Empresa de Mantenimiento $superMail";
									}
									print $doc.'<br>';
									ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
									ManagerUtils::deleteFile($doc);
									if(file_exists($dirName)){
										ManagerUtils::deleteDir($dirName);
									}
									if(!file_exists($dirName)){
										$dir			= mkDir($dirName);
									}
								}catch(Exception $e){
									fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> del super <strong>".ManagerUtils::stripAccents($superName)." Error:".$e."</strong>.<br/><br/>");
									$error=true;
								}
								if($error != true){
									ManagerUtils::setSentDocs($column="sent_".$userType, $whereClauseArray);
									ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
									fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
								}
								else{
									$error = false;

								}
							}
							else{
								fwrite($ddf, "<font color='red'>El usuario <strong>".ManagerUtils::stripAccents($superName)."</strong> no tiene asociada ninguna cuenta de correo.<br/><br/>");
							}
							$envio_adicional= $envio_adicional_aux;
							$superName		= $superNameAux;
							$commName		= $commNameAux;
							$superUser		= $superUserAux;
							$superMail		= $superMailAux;
							if($superMail != "" && $superMail != "-"){
								$i++;
								if($i%2==0){
									$color	= "black";
								}
								else{
									$color	= "green";
								}
								fwrite($ddf, "<font color='$color'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								if(file_exists($dirName)){
									ManagerUtils::deleteDir($dirName);
								}
								$date			= date("d_m_Y");
								if(!file_exists($dirName)){
									$dir			= mkDir($dirName);
								}
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
							}
							$whereClauseArray = array();
							$whereClauseArray[]	= "docId=$docOid";
						}
						else{
							if($superMailAux != "" && $superMailAux != "-"){
								$whereClauseArray[]	= "docId=$docOid";
								/* En este if comprobamos que seguimos tratando los documentos del mismo usuario, por lo que añadimos el documento al zip y continuamos.*/
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commNameAux)."</strong><br/><br/>");
							}
						}
					}
					if(($i>0 || $pendiente) && $superMail != "" && $superMail != "-"){
						$zipName	= $dirName.".zip";
						$zip 		= new PclZip($dirName.".zip");
						$docs[0]	= $dirName;
						$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
						if ($v_list == 0) {
							die ("Error: " . $doc->errorInfo(true));
						}
						$doc	= $_SESSION['dir_root'].$zipName;

						try{
							if($userType=='admin'){
								$asunto	= "Envio al administrador $superMail";
							}
							else if($userType=='em1'){
								$asunto	= "Envio a la Empresa de mantenimiento $superMail";
							}
							else if($userType=='em2'){
								$asunto	= "Envio a la Empresa de Mantenimiento $superMail";
							}
							//print $doc.' if segundo';
							//exit;
							ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
							ManagerUtils::deleteFile($doc);
							if(file_exists($dirName)){
								ManagerUtils::deleteDir($dirName);
							}
							if(!file_exists($dirName)){
								$dir			= mkDir($dirName);
							}
						}catch(Exception $e){
							fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)." Error: ".$e."</strong>.<br/><br/>");
							$error=true;
						}
						if($error != true){
							ManagerUtils::setSentDocs($column="sent_".$userType, $whereClauseArray);
							ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
							fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
						}
						else{
							$error = false;
						}
						//fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$adminMail</strong> del Administrador: <strong>".ManagerUtils::stripAccents($adminName)."</strong></font><br/><br/><br/>");
						//ManagerUtils::deleteDir($dirName);
						//ManagerUtils::deleteFile($zipName);
					}

					fwrite($ddf, "<font color='green'>El envio se ha realizado correctamente</font>");
					fclose($ddf); /* Cerramos el fichero de log*/
				}
				else{
					fwrite($ddf, "<font color='red'>No hay documentos a enviar</font>");
				}
			}catch(Exception $e){
				fwrite($ddf, "<font color='red'>Error en el env&iacute;o</font>");
			}
			if(file_exists(@$dirName)){
				ManagerUtils::deleteDir($dirName);
			}
			if(file_exists(@$doc)){
				ManagerUtils::deleteFile($doc);
			}

		}


		/* Método genérico que sirve para el envío de mails a las entidades de mantenimiento 2, reciben documentos que no sean facturas */
		public static function sendDocsEM2(){
			try{
		        Validator::checkSuperUserInSession();

		        //traigo todos los documentos, que no sean de tipo factura,que estan sin enviar a las em1
				$sql = "SELECT * FROM docs 
				inner join watercodes on docs.wcFk = watercodes.wcOid 
				inner join users on watercodes.userFk = users.userId
				inner join doctypes on docs.docTypeFk = doctypes.docTypeOid
				where docs.sent_em2 != '1' and docs.docTypeFk != '1' and docs.docTypeFk != '101' order by users.name";

				//tengo que verificar que los usuarios correspondientes a esos documentos tienen asociada una em2
				//hago un array con los documentos definitivos
				if(SQLHelper::getDaos($sql) != NULL){
					$docDaos1 = SQLHelper::executeListQuery($sql);
					$docDaos = array();

					while ($doc1 = mysql_fetch_assoc($docDaos1)) {
						$sql1 = "SELECT * FROM usersrelhistory 
						inner join users on usersrelhistory.superCommFk = users.userId 
						where usersrelhistory.commFk = ".$doc1['userFk']." and users.userTypeFk = '4' and usersrelhistory.endDate = 'void' ";

						//Compruebo que la consulta trae alguna fila
						if(SQLHelper::getDaos($sql1) != NULL){
							$doc2 = SQLHelper::getDao($sql1);
							$doc['docId'] = $doc1["docId"];
							$doc['Em2User'] = $doc2["usr"];
							$doc['Em2Mail'] = $doc2["mail"];
							$doc['Em2Name'] = $doc2["name"];
							$doc['envio_adicional'] = $doc2["envio_adicional"];
							$doc['commUser'] = $doc1["usr"];
							$doc['docTypeFk'] = $doc1["docTypeFk"];
							$doc['docTypeName'] = $doc1["docTypeName"];
							$doc['wcOid'] = $doc1["wcOid"];
							$doc['wcName'] = $doc1["wcName"];
							$doc['docName'] = $doc1["docName"];
							$doc['commName'] = $doc1["name"];

							$docDaos[] = $doc;
						}
					}
				}

				/*print '<pre>';
				print_r($docDaos);
				print '</pre>';
				exit;*/
				
				if($docDaos != NULL){
					$docs			= array();		/* Documentos a enviar por correo.*/

					/* El mail es necesario a la hora de enviar el correo con los docs.*/
					$superMail		= "";			/* Mail al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superMailAux	= "";			/* Mail del administrador al que pertenece el doc que tratamos en cada iteración. En el momento en el que sea diferente a $adminMail hay que enviar los docs a este último.*/

					/* El nombre de usuario de la comunidad es necesario a la hora de construir la ruta al documento.*/
					$commUser		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$commUserAux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					/* Variable donde almacenamos si ha habido un error en el envío.*/
					$error			= false;

					/* El nombre del administrador y el de la comunidad se utilizan para informar el log.*/
					$superName		= "";
					$commName		= "";
					$superNameAux	= "";
					$commNameAux	= "";

					/* NOTA: De momento es temporal, para saber con qué administrador se trabaja en cada momento.*/
					$superUser		= "";			/* Username	del administrador al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superUserAux	= "";			/* Username del administrador al que pertenece el doc que tratamos en cada iteración.*/

					/* Campo donde se guardan los mails a los que hay que enviar también el zip con los docs.*/
					$envio_adicional		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$envio_adicional_aux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					$whereClauseArray	= array();	/* Esto se utiliza para el update que setea sent=1 en los documentos recién enviados.*/

					$pendiente		= false;		/* Como los correos se envían cuando se detecta que el administrador actual no es igual al tratado en la iteración anterior, hay que dar un tratamiento especial al último administrador.*/

					$date			= date("d_m_Y");
					$dirName		= "AQUALAB_$date";

					if(!file_exists($dirName)){
						$dir			= mkDir($dirName);
					}
					//print $dir.' '.$dirName;
					$i				= 0;
					$ddf 			= fopen($_SESSION['sentEm2Log'],'w');	/* Abro el ficheor de log con los envíos.*/
					foreach ($docDaos as $key => $docDao) {
					//while($docDao = mysql_fetch_array($docDaos)){
						$docOid			= $docDao['docId'];
						$superUserAux	= $docDao['Em2User'];
						$superMailAux	= $docDao['Em2Mail'];
						$commUserAux	= $docDao['commUser'];
						$docTypeOid		= $docDao['docTypeFk'];
						$docTypeName	= ManagerUtils::stripAccents(utf8_encode($docDao['docTypeName'])); /* Se utiliza para contrsuir el nombre del documento.*/
						$wcOid			= $docDao['wcOid'];
						$envio_adicional_aux= $docDao['envio_adicional'];
						$docName		= $docDao['docName'];
						$encodedWcName	= utf8_encode($docDao['wcName']);
						$commNameAux	= utf8_encode($docDao['commName']);
						$superNameAux	= utf8_encode($docDao['Em2Name']);
						$newDocName		= ManagerUtils::getFileNameFromDao3($docDao); 		/* Nombre del fichero tal y como se va a crear. */
						$docPath		= ManagerUtils::getFilePath($docTypeOid, $commUserAux, $docName, $wcOid, $docOid, $sendToUserType='em2');

						if($superUser == ""){
							/* Añadimos a la clausula where (que se usa para marcar los documentos que han sido enviados) el documento que tratamos en esta iteración.*/
							$whereClauseArray[]	= "docId=$docOid";
							$superName	= utf8_encode($docDao['Em2Name']);
							$commName	= utf8_encode($docDao['commName']);
							$superMail	= $docDao['Em2Mail'];
							$superUser	= $docDao['Em2User'];
							$envio_adicional = $docDao['envio_adicional'];
							if($superMail != "" && $superMail != "-"){
								fwrite($ddf, "<font color='black'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								/* En este if sólo entramos en la primera iteración. */
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								$pendiente = true;
							}
						}
						else if($superUser != $superUserAux){
							$zipName	= $dirName.".zip";
							$zip 		= new PclZip($dirName.".zip");
							$docs[0]	= $dirName;
							$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
							if ($v_list == 0) {
								die ("Error: " . $doc->errorInfo(true));
							}

							$doc	= $_SESSION['dir_root'].$zipName;
							if($superMail != "" && $superMail != "-"){
								try{
									/* Este es el anterior (INICIO)*/
									//ManagerUtils::sendUserDocs($superMail, $doc);
									/* Este es el anterior (FIN)*/
									$asunto	= "Envio a la Empresa de mantenimiento $superMail";
									
									print $doc.'<br>';
									ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
									ManagerUtils::deleteFile($doc);
									if(file_exists($dirName)){
										ManagerUtils::deleteDir($dirName);
									}
									if(!file_exists($dirName)){
										$dir			= mkDir($dirName);
									}
								}catch(Exception $e){
									fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> del super <strong>".ManagerUtils::stripAccents($superName)." Error:".$e."</strong>.<br/><br/>");
									$error=true;
								}
								if($error != true){
									ManagerUtils::setSentDocs($column="sent_em2", $whereClauseArray);
									fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
								}
								else{
									$error = false;

								}
							}
							else{
								fwrite($ddf, "<font color='red'>El usuario <strong>".ManagerUtils::stripAccents($superName)."</strong> no tiene asociada ninguna cuenta de correo.<br/><br/>");
							}
							$envio_adicional= $envio_adicional_aux;
							$superName		= $superNameAux;
							$commName		= $commNameAux;
							$superUser		= $superUserAux;
							$superMail		= $superMailAux;
							if($superMail != "" && $superMail != "-"){
								$i++;
								if($i%2==0){
									$color	= "black";
								}
								else{
									$color	= "green";
								}
								fwrite($ddf, "<font color='$color'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								if(file_exists($dirName)){
									ManagerUtils::deleteDir($dirName);
								}
								$date			= date("d_m_Y");
								if(!file_exists($dirName)){
									$dir			= mkDir($dirName);
								}
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
							}
							$whereClauseArray = array();
							$whereClauseArray[]	= "docId=$docOid";
						}
						else{
							if($superMailAux != "" && $superMailAux != "-"){
								$whereClauseArray[]	= "docId=$docOid";
								/* En este if comprobamos que seguimos tratando los documentos del mismo usuario, por lo que añadimos el documento al zip y continuamos.*/
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commNameAux)."</strong><br/><br/>");
							}
						}
					}
					if(($i>0 || $pendiente) && $superMail != "" && $superMail != "-"){
						$zipName	= $dirName.".zip";
						$zip 		= new PclZip($dirName.".zip");
						$docs[0]	= $dirName;
						$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
						if ($v_list == 0) {
							die ("Error: " . $doc->errorInfo(true));
						}
						$doc	= $_SESSION['dir_root'].$zipName;

						try{
							$asunto	= "Envio a la Empresa de Mantenimiento $superMail";
							
							//print $doc.' if segundo';
							//exit;
							ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
							ManagerUtils::deleteFile($doc);
							if(file_exists($dirName)){
								ManagerUtils::deleteDir($dirName);
							}
							if(!file_exists($dirName)){
								$dir			= mkDir($dirName);
							}
						}catch(Exception $e){
							fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)." Error: ".$e."</strong>.<br/><br/>");
							$error=true;
						}
						if($error != true){
							ManagerUtils::setSentDocs($column="sent_em2", $whereClauseArray);
							fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
						}
						else{
							$error = false;
						}
						//fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$adminMail</strong> del Administrador: <strong>".ManagerUtils::stripAccents($adminName)."</strong></font><br/><br/><br/>");
						//ManagerUtils::deleteDir($dirName);
						//ManagerUtils::deleteFile($zipName);
					}

					fwrite($ddf, "<font color='green'>El envio se ha realizado correctamente</font>");
					fclose($ddf); /* Cerramos el fichero de log*/
				}
				else{
					fwrite($ddf, "<font color='red'>No hay documentos a enviar</font>");
				}
			}catch(Exception $e){
				fwrite($ddf, "<font color='red'>Error en el env&iacute;o</font>");
			}
			if(file_exists(@$dirName)){
				ManagerUtils::deleteDir($dirName);
			}
			if(file_exists(@$doc)){
				ManagerUtils::deleteFile($doc);
			}

		}


		/* Método genérico que sirve para el envío de mails a las entidades de mantenimiento 1*/
		public static function sendDocsEM1(){
			try{
		        Validator::checkSuperUserInSession();

		        //traigo todos los documentos que estén sin enviar a las em1
				$sql = "SELECT * FROM docs 
				inner join watercodes on docs.wcFk = watercodes.wcOid 
				inner join users on watercodes.userFk = users.userId
				inner join doctypes on docs.docTypeFk = doctypes.docTypeOid
				where docs.sent_em1 != '1' order by users.name";

				$docDaos1	= SQLHelper::executeListQuery($sql);

				//hago un array con los documentos definitivos
				$docDaos = array();

				while ($doc1 = mysql_fetch_assoc($docDaos1)) {
					
					//tengo que verificar que los usuarios correspondientes a esos documentos tienen asociada una em1 activa
					$sql1 = "SELECT * FROM usersrelhistory 
						inner join users on usersrelhistory.superCommFk = users.userId 
						where usersrelhistory.commFk = ".$doc1['userFk']." and users.userTypeFk = '3' and usersrelhistory.endDate = 'void' ";

					//Compruebo que la consulta trae alguna fila
					if(SQLHelper::getDaos($sql1) != NULL){
						$doc2 = SQLHelper::getDao($sql1);
						$doc['docId'] = $doc1["docId"];
						$doc['Em1User'] = $doc1["usr"];
						$doc['Em1Mail'] = $doc1["mail"];
						$doc['Em1Name'] = $doc1["name"];
						$doc['envio_adicional'] = $doc1["envio_adicional"];
						$doc['commUser'] = $doc1["usr"];
						$doc['docTypeFk'] = $doc1["docTypeFk"];
						$doc['docTypeName'] = $doc1["docTypeName"];
						$doc['wcOid'] = $doc1["wcOid"];
						$doc['wcName'] = $doc1["wcName"];
						$doc['docName'] = $doc1["docName"];
						$doc['commName'] = $doc1["name"];

						$docDaos[] = $doc;

						// Si el documento no es una factura lo incluyo en un nuevo array para enviárselo al administrador del watercode
						if($doc1['docTypeFk'] != '1' && $doc1['docTypeFk'] != '101'){
							$sql2 = "SELECT * FROM watercodes
							inner join users on watercodes.em1Fk = users.userId 
							where watercodes.em1Fk = ".$doc1['em1Fk']." order by users.name";

							if(SQLHelper::getDaos($sql2) != NULL){
								$em1Fk = SQLHelper::getDao($sql2);

								$docW['docId'] = $doc1["docId"];
								$docW['WatercodeUser'] = $em1Fk["usr"];
								$docW['WatercodeMail'] = $em1Fk["mail"];
								$docW['WatercodeName'] = $em1Fk["name"];
								$docW['envio_adicional'] = $em1Fk["envio_adicional"];
								$docW['commUser'] = $doc1["usr"];
								$docW['docTypeFk'] = $doc1["docTypeFk"];
								$docW['docTypeName'] = $doc1["docTypeName"];
								$docW['wcOid'] = $doc1["wcOid"];
								$docW['wcName'] = $doc1["wcName"];
								$docW['docName'] = $doc1["docName"];
								$docW['commName'] = $doc1["name"];

								$docWatercodes[] = $docW;
							}
							
						}
					}
					
				}
				
				if($docDaos != NULL){
					$docs			= array();		/* Documentos a enviar por correo.*/

					/* El mail es necesario a la hora de enviar el correo con los docs.*/
					$superMail		= "";			/* Mail al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superMailAux	= "";			/* Mail del administrador al que pertenece el doc que tratamos en cada iteración. En el momento en el que sea diferente a $adminMail hay que enviar los docs a este último.*/

					/* El nombre de usuario de la comunidad es necesario a la hora de construir la ruta al documento.*/
					$commUser		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$commUserAux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					/* Variable donde almacenamos si ha habido un error en el envío.*/
					$error			= false;

					/* El nombre del administrador y el de la comunidad se utilizan para informar el log.*/
					$superName		= "";
					$commName		= "";
					$superNameAux	= "";
					$commNameAux	= "";

					/* NOTA: De momento es temporal, para saber con qué administrador se trabaja en cada momento.*/
					$superUser		= "";			/* Username	del administrador al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superUserAux	= "";			/* Username del administrador al que pertenece el doc que tratamos en cada iteración.*/

					/* Campo donde se guardan los mails a los que hay que enviar también el zip con los docs.*/
					$envio_adicional		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$envio_adicional_aux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					$whereClauseArray	= array();	/* Esto se utiliza para el update que setea sent=1 en los documentos recién enviados.*/

					$pendiente		= false;		/* Como los correos se envían cuando se detecta que el administrador actual no es igual al tratado en la iteración anterior, hay que dar un tratamiento especial al último administrador.*/

					$date			= date("d_m_Y");
					$dirName		= "AQUALAB_$date";

					if(!file_exists($dirName)){
						$dir			= mkDir($dirName);
					}
					//print $dir.' '.$dirName;
					$i				= 0;
					$ddf 			= fopen($_SESSION['sentEm1Log'],'w');	/* Abro el ficheor de log con los envíos.*/
					foreach ($docDaos as $key => $docDao) {
					//while($docDao = mysql_fetch_array($docDaos)){

						$docOid			= $docDao['docId'];
						$superUserAux	= $docDao['Em1User'];
						$superMailAux	= $docDao['Em1Mail'];
						$commUserAux	= $docDao['commUser'];
						$docTypeOid		= $docDao['docTypeFk'];
						$docTypeName	= ManagerUtils::stripAccents(utf8_encode($docDao['docTypeName'])); /* Se utiliza para contrsuir el nombre del documento.*/
						$wcOid			= $docDao['wcOid'];
						$envio_adicional_aux= $docDao['envio_adicional'];
						$docName		= $docDao['docName'];
						$encodedWcName	= utf8_encode($docDao['wcName']);
						$commNameAux	= utf8_encode($docDao['commName']);
						$superNameAux	= utf8_encode($docDao['Em1Name']);
						$newDocName		= ManagerUtils::getFileNameFromDao3($docDao); 		/* Nombre del fichero tal y como se va a crear. */
						$docPath		= ManagerUtils::getFilePath($docTypeOid, $commUserAux, $docName, $wcOid, $docOid, $sendToUserType='em1');



						if($superUser == ""){
							/* Añadimos a la clausula where (que se usa para marcar los documentos que han sido enviados) el documento que tratamos en esta iteración.*/
							$whereClauseArray[]	= "docId=$docOid";
							$superName	= utf8_encode($docDao['Em1Name']);
							$commName	= utf8_encode($docDao['commName']);
							$superMail	= $docDao['Em1Mail'];
							$superUser	= $docDao['Em1User'];
							$envio_adicional = $docDao['envio_adicional'];
							if($superMail != "" && $superMail != "-"){
								fwrite($ddf, "<font color='black'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								/* En este if sólo entramos en la primera iteración. */
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								$pendiente = true;
							}
						}
						else if($superUser != $superUserAux){
							$zipName	= $dirName.".zip";
							$zip 		= new PclZip($dirName.".zip");
							$docs[0]	= $dirName;
							$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
							if ($v_list == 0) {
								die ("Error: " . $doc->errorInfo(true));
							}

							$doc	= $_SESSION['dir_root'].$zipName;
							if($superMail != "" && $superMail != "-"){
								try{
									/* Este es el anterior (INICIO)*/
									//ManagerUtils::sendUserDocs($superMail, $doc);
									/* Este es el anterior (FIN)*/
									$asunto	= "Envio a la comunidad $superMail";
									
									print $doc.'<br>';
									ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
									ManagerUtils::deleteFile($doc);
									if(file_exists($dirName)){
										ManagerUtils::deleteDir($dirName);
									}
									if(!file_exists($dirName)){
										$dir			= mkDir($dirName);
									}
								}catch(Exception $e){
									fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> del super <strong>".ManagerUtils::stripAccents($superName)." Error:".$e."</strong>.<br/><br/>");
									$error=true;
								}
								if($error != true){
									ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
									ManagerUtils::setSentDocs($column="sent_comm", $whereClauseArray);
									fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
								}
								else{
									$error = false;

								}
							}
							else{
								fwrite($ddf, "<font color='red'>El usuario <strong>".ManagerUtils::stripAccents($superName)."</strong> no tiene asociada ninguna cuenta de correo.<br/><br/>");
							}
							$envio_adicional= $envio_adicional_aux;
							$superName		= $superNameAux;
							$commName		= $commNameAux;
							$superUser		= $superUserAux;
							$superMail		= $superMailAux;
							if($superMail != "" && $superMail != "-"){
								$i++;
								if($i%2==0){
									$color	= "black";
								}
								else{
									$color	= "green";
								}
								fwrite($ddf, "<font color='$color'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								if(file_exists($dirName)){
									ManagerUtils::deleteDir($dirName);
								}
								$date			= date("d_m_Y");
								if(!file_exists($dirName)){
									$dir			= mkDir($dirName);
								}
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
							}
							$whereClauseArray = array();
							$whereClauseArray[]	= "docId=$docOid";
						}
						else{
							if($superMailAux != "" && $superMailAux != "-"){
								$whereClauseArray[]	= "docId=$docOid";
								/* En este if comprobamos que seguimos tratando los documentos del mismo usuario, por lo que añadimos el documento al zip y continuamos.*/
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commNameAux)."</strong><br/><br/>");
							}
						}
					}
					if(($i>0 || $pendiente) && $superMail != "" && $superMail != "-"){
						$zipName	= $dirName.".zip";
						$zip 		= new PclZip($dirName.".zip");
						$docs[0]	= $dirName;
						$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
						if ($v_list == 0) {
							die ("Error: " . $doc->errorInfo(true));
						}
						$doc	= $_SESSION['dir_root'].$zipName;

						try{
							$asunto	= "Envio a la Empresa de Mantenimiento $superMail";
							
							//print $doc.' if segundo';
							//exit;
							ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
							ManagerUtils::deleteFile($doc);
							if(file_exists($dirName)){
								ManagerUtils::deleteDir($dirName);
							}
							if(!file_exists($dirName)){
								$dir			= mkDir($dirName);
							}
						}catch(Exception $e){
							fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)." Error: ".$e."</strong>.<br/><br/>");
							$error=true;
						}
						if($error != true){
							ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
							ManagerUtils::setSentDocs($column="sent_comm", $whereClauseArray);
							fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
						}
						else{
							$error = false;
						}
						//fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$adminMail</strong> del Administrador: <strong>".ManagerUtils::stripAccents($adminName)."</strong></font><br/><br/><br/>");
						//ManagerUtils::deleteDir($dirName);
						//ManagerUtils::deleteFile($zipName);
					}

					fwrite($ddf, "<font color='green'>El envio se ha realizado correctamente</font>");
					fclose($ddf); /* Cerramos el fichero de log*/
				}
				else{
					fwrite($ddf, "<font color='red'>No hay documentos a enviar</font>");
				}


				//envio a los admin del watercode
				if($docWatercodes != NULL){
					$docs			= array();		/* Documentos a enviar por correo.*/

					/* El mail es necesario a la hora de enviar el correo con los docs.*/
					$superMail		= "";			/* Mail al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superMailAux	= "";			/* Mail del administrador al que pertenece el doc que tratamos en cada iteración. En el momento en el que sea diferente a $adminMail hay que enviar los docs a este último.*/

					/* El nombre de usuario de la comunidad es necesario a la hora de construir la ruta al documento.*/
					$commUser		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$commUserAux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					/* Variable donde almacenamos si ha habido un error en el envío.*/
					$error			= false;

					/* El nombre del administrador y el de la comunidad se utilizan para informar el log.*/
					$superName		= "";
					$commName		= "";
					$superNameAux	= "";
					$commNameAux	= "";

					/* NOTA: De momento es temporal, para saber con qué administrador se trabaja en cada momento.*/
					$superUser		= "";			/* Username	del administrador al que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario).*/
					$superUserAux	= "";			/* Username del administrador al que pertenece el doc que tratamos en cada iteración.*/

					/* Campo donde se guardan los mails a los que hay que enviar también el zip con los docs.*/
					$envio_adicional		= "";			/* Username	de la comunidad a la que pertenecen los docs que vamos a enviar el correo en la iteración correspondiente (es decir, cuando ya no quedan más documentos de ese usuario). */
					$envio_adicional_aux	= "";			/* Username de la comunidad a la que pertenece el doc que tratamos en cada iteración.*/

					$whereClauseArray	= array();	/* Esto se utiliza para el update que setea sent=1 en los documentos recién enviados.*/

					$pendiente		= false;		/* Como los correos se envían cuando se detecta que el administrador actual no es igual al tratado en la iteración anterior, hay que dar un tratamiento especial al último administrador.*/

					$date			= date("d_m_Y");
					$dirName		= "AQUALAB_$date";

					if(!file_exists($dirName)){
						$dir			= mkDir($dirName);
					}
					//print $dir.' '.$dirName;
					$i				= 0;
					$ddf 			= fopen($_SESSION['sentAdminWatercodeLog'],'w');	/* Abro el fichero de log con los envíos.*/
					foreach ($docWatercodes as $key => $docDao) {
					//while($docDao = mysql_fetch_array($docDaos)){
						$docOid			= $docDao['docId'];
						$superUserAux	= $docDao['WatercodeUser'];
						$superMailAux	= $docDao['WatercodeMail'];
						$commUserAux	= $docDao['commUser'];
						$docTypeOid		= $docDao['docTypeFk'];
						$docTypeName	= ManagerUtils::stripAccents(utf8_encode($docDao['docTypeName'])); /* Se utiliza para contrsuir el nombre del documento.*/
						$wcOid			= $docDao['wcOid'];
						$envio_adicional_aux= $docDao['envio_adicional'];
						$docName		= $docDao['docName'];
						$encodedWcName	= utf8_encode($docDao['wcName']);
						$commNameAux	= utf8_encode($docDao['commName']);
						$superNameAux	= utf8_encode($docDao['WatercodeName']);
						$newDocName		= ManagerUtils::getFileNameFromDao3($docDao); 		/* Nombre del fichero tal y como se va a crear. */
						$docPath		= ManagerUtils::getFilePath($docTypeOid, $commUserAux, $docName, $wcOid, $docOid, $sendToUserType='admin');

						if($superUser == ""){
							/* Añadimos a la clausula where (que se usa para marcar los documentos que han sido enviados) el documento que tratamos en esta iteración.*/
							$whereClauseArray[]	= "docId=$docOid";
							$superName	= utf8_encode($docDao['WatercodeName']);
							$commName	= utf8_encode($docDao['commName']);
							$superMail	= $docDao['WatercodeMail'];
							$superUser	= $docDao['WatercodeUser'];
							$envio_adicional = $docDao['envio_adicional'];
							if($superMail != "" && $superMail != "-"){
								fwrite($ddf, "<font color='black'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								/* En este if sólo entramos en la primera iteración. */
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
								$pendiente = true;
							}
						}
						else if($superUser != $superUserAux){
							$zipName	= $dirName.".zip";
							$zip 		= new PclZip($dirName.".zip");
							$docs[0]	= $dirName;
							$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
							if ($v_list == 0) {
								die ("Error: " . $doc->errorInfo(true));
							}

							$doc	= $_SESSION['dir_root'].$zipName;
							if($superMail != "" && $superMail != "-"){
								try{
									/* Este es el anterior (INICIO)*/
									//ManagerUtils::sendUserDocs($superMail, $doc);
									/* Este es el anterior (FIN)*/
									$asunto	= "Envio a la Empresa de mantenimiento $superMail";
									
									print $doc.'<br>';
									ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
									ManagerUtils::deleteFile($doc);
									if(file_exists($dirName)){
										ManagerUtils::deleteDir($dirName);
									}
									if(!file_exists($dirName)){
										$dir			= mkDir($dirName);
									}
								}catch(Exception $e){
									fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> del super <strong>".ManagerUtils::stripAccents($superName)." Error:".$e."</strong>.<br/><br/>");
									$error=true;
								}
								if($error != true){
									//ManagerUtils::setSentDocs($column="sent_em1", $whereClauseArray);
									fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
								}
								else{
									$error = false;

								}
							}
							else{
								fwrite($ddf, "<font color='red'>El usuario <strong>".ManagerUtils::stripAccents($superName)."</strong> no tiene asociada ninguna cuenta de correo.<br/><br/>");
							}
							$envio_adicional= $envio_adicional_aux;
							$superName		= $superNameAux;
							$commName		= $commNameAux;
							$superUser		= $superUserAux;
							$superMail		= $superMailAux;
							if($superMail != "" && $superMail != "-"){
								$i++;
								if($i%2==0){
									$color	= "black";
								}
								else{
									$color	= "green";
								}
								fwrite($ddf, "<font color='$color'>Cargando documentos para: <strong>".ManagerUtils::stripAccents($superName)."</strong><br/><br/>");
								if(file_exists($dirName)){
									ManagerUtils::deleteDir($dirName);
								}
								$date			= date("d_m_Y");
								if(!file_exists($dirName)){
									$dir			= mkDir($dirName);
								}
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commName)."</strong><br/><br/>");
							}
							$whereClauseArray = array();
							$whereClauseArray[]	= "docId=$docOid";
						}
						else{
							if($superMailAux != "" && $superMailAux != "-"){
								$whereClauseArray[]	= "docId=$docOid";
								/* En este if comprobamos que seguimos tratando los documentos del mismo usuario, por lo que añadimos el documento al zip y continuamos.*/
								copy($docPath, $dirName."/".$newDocName);
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tipo de documento:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$docTypeName</strong><br/>");
								if($docTypeOid != Cte::$FACTURA && $docTypeOid != Cte::$RECIBO){
									fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo de agua:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($encodedWcName)."</strong><br/>");
								}
								fwrite($ddf, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comunidad:<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ManagerUtils::stripAccents($commNameAux)."</strong><br/><br/>");
							}
						}
					}
					if(($i>0 || $pendiente) && $superMail != "" && $superMail != "-"){
						$zipName	= $dirName.".zip";
						$zip 		= new PclZip($dirName.".zip");
						$docs[0]	= $dirName;
						$v_list = $zip->create($docs, PCLZIP_OPT_REMOVE_ALL_PATH);
						if ($v_list == 0) {
							die ("Error: " . $doc->errorInfo(true));
						}
						$doc	= $_SESSION['dir_root'].$zipName;

						try{
							$asunto	= "Envio a la Empresa de Mantenimiento $superMail";
							
							//print $doc.' if segundo';
							//exit;
							ManagerUtils::sendUserDocs($superMail, $doc, $envio_adicional, $asunto,$docTypeOid);
							ManagerUtils::deleteFile($doc);
							if(file_exists($dirName)){
								ManagerUtils::deleteDir($dirName);
							}
							if(!file_exists($dirName)){
								$dir			= mkDir($dirName);
							}
						}catch(Exception $e){
							fwrite($ddf, "<font color='red'>Error en el env&iacute;o de los documentos da la cuenta de correo <strong>$superMail</strong> de <strong>".ManagerUtils::stripAccents($superName)." Error: ".$e."</strong>.<br/><br/>");
							$error=true;
						}
						if($error != true){
							//ManagerUtils::setSentDocs($column="sent_".$userType, $whereClauseArray);
							fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$superMail</strong> <strong>".ManagerUtils::stripAccents($superName)."</strong></font><br/><br/><br/>");
						}
						else{
							$error = false;
						}
						//fwrite($ddf, "Documentos enviados a la cuenta de correo <strong>$adminMail</strong> del Administrador: <strong>".ManagerUtils::stripAccents($adminName)."</strong></font><br/><br/><br/>");
						//ManagerUtils::deleteDir($dirName);
						//ManagerUtils::deleteFile($zipName);
					}

					fwrite($ddf, "<font color='green'>El envio se ha realizado correctamente</font>");
					fclose($ddf); /* Cerramos el fichero de log*/
				}
				else{
					fwrite($ddf, "<font color='red'>No hay documentos a enviar</font>");
				}

			}catch(Exception $e){
				fwrite($ddf, "<font color='red'>Error en el env&iacute;o</font>");
			}
			if(file_exists(@$dirName)){
				ManagerUtils::deleteDir($dirName);
			}
			if(file_exists(@$doc)){
				ManagerUtils::deleteFile($doc);
			}

		}

	}
?>
