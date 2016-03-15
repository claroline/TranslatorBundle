export default class TranslatorController {
	constructor($http, $cacheFactory, $sce, TranslatorAPIService) {
		this.$http                = $http
		this.$cacheFactory        = $cacheFactory
		this.$sce                 = $sce
		this.TranslatorAPIService = TranslatorAPIService

		this.$http.defaults.cache = true;
		this.lang                 = 'fr';
		this.bundle               = 'CoreBundle';
		this.vendor               = 'Claroline';
		this.repository           = 'ClarolineCoreBundle';
		this.preferedLang         = 'fr';
		this.langs                = ['fr', 'en'];
		this.translations         = [];
		this.preferedTranslations = [];
		this.search               = '';
		this.translationInfos     = [];
		// not pretty but w/e
		this.isAdmin              = AngularApp.isAdmin;

	 	this.dataTableOptions = {
	 		scrollbarV: false,
	 		columnMode: 'force',
	        headerHeight: 0,
	        footerHeight: 50,
	 		paging: {
	 			externalPaging: true,
	 			size: 10
	 		},
	 		column: [{
	 			name: "Translation",
	 			prop: "translation"
	 		}]
	 	}

 		this.TranslatorAPIService.repositories().then(d => this.repositories = d.data)
		this.TranslatorAPIService.locales().then(d => this.langs = d.data)
	}

	getCurrentCacheKey(type) {
		const lang = type === 'prefered' ? this.preferedLang: this.lang;

		return this.search === '' ?
			Routing.generate(
				'claroline_translator_get_latest', 
				{'vendor': this.vendor, 'bundle': this.bundle, 'lang':  lang}
			):
			Routing.generate(
				'claroline_translator_search_latest', 
				{'vendor': this.vendor, 'bundle': this.bundle, 'lang': lang, 'search': this.search}
			);
	}

	setTranslations(type, offset, size, translations) {
		//cloning the object...
  		const cache = JSON.parse(JSON.stringify(translations));

		if (type === 'current' ) {
			this.translations = translations;

			for (let i = 0; i < this.translations.length; i++) {
				//console.log($scope.translations[i]);
				if (this.translations[i].admin_lock) {
					console.log("admin lock for ", this.translations[i])
					this.translations.splice(i, 1)
				}
			}

		} else {
			this.preferedTranslations = translations;
		}

		this.dataTableOptions.paging.count = translations.length;

		const set = translations.splice(offset * size, size)
        // only insert items i don't already have
        set.forEach((r, i) => {
            let idx = i + (offset * size)

            if (type === 'current') {
            	this.translations[idx] = r
            } else {
            	this.preferedTranslations[idx] = r
            }
            
        });

        this.$cacheFactory.get('$http').put(this.getCurrentCacheKey(type), cache)
	}

	cacheRow(row) {
		//This stupid plugin needs the array splice function and truncate every results.
		//Because of the this the cache handling is... let's say annoying.
		const httpCache = this.$cacheFactory.get('$http')
		const fromCache = httpCache.get(this.getCurrentCacheKey())
		const parsed = fromCache;

		for (let i = 0; i < parsed.length; i++) {
			if (parsed[i].id === row.id) parsed[i] = row
		}

		httpCache.put(this.getCurrentCacheKey(), parsed)
	}

	loadTranslations(type) {
		const offset    = this.dataTableOptions.paging.offset || 0;
		const size      = this.dataTableOptions.paging.size || 10;	
		const search    = this.search;
		const httpCache = this.$cacheFactory.get('$http');
		const fromCache = httpCache.get(this.getCurrentCacheKey(type));

		if (fromCache) {
			if (fromCache instanceof Array) {
				const parsed = (fromCache[3] === "OK") ? JSON.parse(fromCache[1]): fromCache;
				this.setTranslations(type, offset, size, parsed);
			} else {
				fromCache.then(function(d) {
					this.setTranslations(type, offset, size, JSON.parse(d.data));
				});
			}

			return;
		}

		if (type === 'current') {
			if (search !== '') {
				this.TranslatorAPIService.search(this.lang, this.vendor, this.bundle, search).then(d =>
					this.setTranslations(type, offset, size, d.data)
				)
			} else {
				this.TranslatorAPIService.load(this.lang, this.vendor, this.bundle).then(d => 
					this.setTranslations(type, offset, size, d.data)
				) 
			}
		} 

		if (type === 'prefered') {
			if (this.search !== '') {
				this.TranslatorAPIService.search(this.preferedLang, this.vendor, this.bundle, search).then(d =>
					this.setTranslations(type, offset, size, d.data)
				) 
			} else {
				this.TranslatorAPIService.load(this.preferedLang, this.vendor, this.bundle).then(d =>
					this.setTranslations(type, offset, size, d.data)
				)
			}		
		}
	}

	findById(type, key) {
		if (type === 'current') {
			for (let i = 0; i < this.translations.length; i++) {
				if (this.translations[i].key === key) {
					return this.translations[i];
				}
			}
		} else {
			for (let i = 0; i < this.preferedTranslations.length; i++) {
				if (this.preferedTranslations[i].key === key) {
					return this.preferedTranslations[i];
				}	
			}
		}
	}

	setPreferedLang(lang) {
		this.preferedLang = lang
		this.loadTranslations('prefered')
	}       

	setLang(lang) {
		this.lang = lang
		this.loadTranslations('current')
	} 
      
  	setRepository(repository) {
		for (let key in repository) {
		    if (repository.hasOwnProperty(key)) {
		    	this.vendor = key
		    	this.bundle = repository[key]
		    	this.repository = this.vendor + this.bundle;
		    	this.loadTranslations('current');
		    	this.loadTranslations('prefered');
		    }
		}
	}

	addTranslation(cell, row, col) {
		const data = {
			'translation_item': row.id, 
			'translation': row.translations[0].translation
		};

		cacheRow(row);

		promise = this.$http.post(Routing.generate('claroline_translator_add_translation'), data).then(d => {
			const myEl = angular.element(document.querySelector('#translator-panel'));
			myEl.prepend(alertSuccess);  
		})

	 	const alertSuccess = '<div class="alert alert-success">' + 
	 		'<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' +
		 	 window.Translator.trans('new_translation', {translation: row.translations[0].translation}, 'translator')
		'</div>'
	}

	loadInfos(row) {
		this.TranslatorAPIService.translationsInfo(this.lang, row.vendor, row.bundle, row.domain, row.key).then(function(d) {
			this.translationInfos[row.id] = d.data;
		});
	}

	clickAdminLock(row) {
		this.TranslatorAPIService.clickAdminLock(row.id).then(d => {
			row.admin_lock = !row.admin_lock;
			this.cacheRow(row);
		});
	}

	clickUserLock(row) {
		TranslatorAPIService.clickUserLock(row.id).then(d => {
			row.user_lock = !row.user_lock;
			this.cacheRow(row);
		});
	}

	paging() {
		this.loadTranslations('current');
		this.loadTranslations('prefered');
	}

	getPreferedTranslation(cell, row, col) {

		var item = this.findById('prefered', row.key);

		return (item !== undefined) ?
			item.translations[0].translation:
			'not found';

		/* Idexed way of doing it... wich won't work because js doesn't know what an indexed array is.
		var idx = $scope.translations.indexOf(row);

		return (typeof $scope.preferedTranslations[idx] !== 'undefined') ?
			$scope.preferedTranslations[idx].translations[0].translation:
			'not found';
		*/
	}

	comment(row) {
		this.TranslatorAPIService.comment(row.id).then(d => {
			const route = Routing.generate('claro_forum_messages', {'subject': d.data.subject_id});
			window.location.href = route;
		});
	}
}

TranslatorController.$inject = ['$http', '$cacheFactory', '$sce', 'TranslatorAPIService']

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               