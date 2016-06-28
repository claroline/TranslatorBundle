import 'angular/angular.min'

import dataTable from 'angular-data-table/release/dataTable.helpers.min'
import bootstrap from 'angular-bootstrap'
import translation from 'angular-ui-translation/angular-translation'

import TranslatorController from './Controller/TranslatorController'
import TranslatorDirective from './Directive/TranslatorDirective'
import TranslatorAPIService from './Service/TranslatorAPIService'

import config from '#/main/core/Resources/modules/interceptorsDefault'

angular.module('GitTranslator', ['data-table', 'ui.bootstrap', 'ui.translation'])
   .service('TranslatorAPIService', TranslatorAPIService)
   .directive('translator',  () => new TranslatorDirective)
   .config(config)
