export default class TranslatorAPIService {
	constructor($http) {
		this.$http = $http
	}

	load(lang, vendor, bundle) {
		return this.$http.get(Routing.generate(
			'claroline_translator_get_latest', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang}
		));
	}

	search(lang, vendor, bundle, search) {
		return this.$http.get(Routing.generate(
			'claroline_translator_search_latest',
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'search': search}
		));
	}

	locales() {
		return this.$http.get(Routing.generate('claroline_translator_langs'));
	}

	repositories() {
		return this.$http.get(Routing.generate('claroline_translator_repositories'));
	}

	translationsInfo(lang, vendor, bundle, domain, key) {
		return this.$http.get(Routing.generate(
			'claroline_translator_get_translation_info', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'key': key, 'domain': domain}
		));
	}

	clickUserLock(id) {
		return this.$http.post(Routing.generate(
			'claroline_translator_user_lock', 
			{'translationItem': id}
		));
	}

	clickAdminLock(id) {
		return this.$http.post(Routing.generate(
			'claroline_translator_admin_lock', 
			{'translationItem': id}
		));
	}

	comment(id) {
		return this.$http.get(Routing.generate(
			'claroline_translator_forum_subject',
			{'translationItem': id}
		));
	}
}

TranslatorAPIService.$inject = ['$http']