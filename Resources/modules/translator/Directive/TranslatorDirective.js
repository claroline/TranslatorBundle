import TranslatorController from '../Controller/TranslatorController'

export default class TranslatorDirective {
	constructor() {
        this.restrict = 'E'
        this.replace = true
        this.controller = TranslatorController
        this.controllerAs = 'tc'
        this.template = require('../Partial/translator.html')
	}
}