DELETE FROM `idcsmart_plugin` WHERE `name`='StripeAli';
insert  into `idcsmart_plugin_hook`(`name`,`status`,`plugin`,`module`,`order`) values ('certification_detail',1,'IdcsmartCertification','addon',0);