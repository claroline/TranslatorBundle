var gitTranslator = angular.module('gitTranslator', ['data-table', 'ui.bootstrap', 'ui.translation']);

//let's do some initialization first.
gitTranslator.config(function ($httpProvider) {
	$httpProvider.interceptors.push(function ($q) {
		return {
			'request': function(config) {
				$('.please-wait').show();

				return config;
			},
			'requestError': function(rejection) {
				$('.please-wait').hide();

				return $q.reject(rejection);
			},	
			'responseError': function(rejection) {
				$('.please-wait').hide();

				return $q.reject(rejection);
			},
			'response': function(response) {
				$('.please-wait').hide();

				return response;
			}
		};
	});
});

gitTranslator.controller('contentCtrl', function(
	$scope, 
	$log,
	$http, 
	$cacheFactory, 
	$sce,
	API
) {
	$http.defaults.cache        = true;
	$scope.lang                 = 'fr';
	$scope.bundle               = 'CoreBundle';
	$scope.vendor               = 'claroline';
	$scope.repository           = 'clarolineCoreBundle';
	$scope.preferedLang         = 'fr';
	$scope.$log                 = $log;
	$scope.$langs               = ['fr', 'en'];
	$scope.translations         = [];
	$scope.preferedTranslations = [];
	$scope.search               = '';
	$scope.translationInfos     = [];
	$scope.isAdmin              = AngularApp.isAdmin;

 	$scope.dataTableOptions = {
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

	API.repositories().then(function(d) {
		$scope.repositories = d.data;
	});

	API.locales().then(function(d) {
		$scope.langs = d.data;
	});    

	var getCurrentCacheKey = function(type) {
		var lang = type === 'prefered' ? $scope.preferedLang: $scope.lang;

		return $scope.search === '' ?
			Routing.generate(
				'claroline_translator_get_latest', 
				{'vendor': $scope.vendor, 'bundle': $scope.bundle, 'lang':  lang}
			):
			Routing.generate(
				'claroline_translator_search_latest', 
				{'vendor': $scope.vendor, 'bundle': $scope.bundle, 'lang': lang, 'search': $scope.search}
			);
	}

	var setTranslations = function(type, offset, size, translations) {
		//cloning the object...
  		var cache = JSON.parse(JSON.stringify(translations));

		if (type === 'current' ) {
			$scope.translations = translations;
		} else {
			$scope.preferedTranslations = translations;
		}

		$scope.dataTableOptions.paging.count = translations.length;

		var set = translations.splice(offset * size, size);
        // only insert items i don't already have
        set.forEach(function(r, i) {
            var idx = i + (offset * size);

            if (type === 'current') {
            	$scope.translations[idx] = r;
            } else {
            	$scope.preferedTranslations[idx] = r;
            }
            
        });

        var httpCache = $cacheFactory.get('$http');
        var cachedResponse = httpCache.put(getCurrentCacheKey(type), cache);
	}

	var cacheRow = function(row) {
		//This stupid plugin needs the array splice function and truncate every results.
		//Because of the this the cache handling is... let's say annoying.
		var httpCache = $cacheFactory.get('$http');
		var fromCache = httpCache.get(getCurrentCacheKey());
		var parsed = fromCache;

		for (var i = 0; i < parsed.length; i++) {
			if (parsed[i].id === row.id) parsed[i] = row;
		}

		var cachedResponse = httpCache.put(getCurrentCacheKey(), parsed);
	}

	var loadTranslations = function(type) {
		var offset    = $scope.dataTableOptions.paging.offset || 0;
		var size      = $scope.dataTableOptions.paging.size || 10;	
		var search    = $scope.search;
		var httpCache = $cacheFactory.get('$http');
		var fromCache = httpCache.get(getCurrentCacheKey(type));

		if (fromCache) {
			if (fromCache instanceof Array) {
				if (fromCache[3] === "OK") {
					var parsed = JSON.parse(fromCache[1]);
				} else {
					var parsed = fromCache;
				}
				setTranslations(type, offset, size, parsed);
			} else {
				fromCache.then(function(d) {
					setTranslations(type, offset, size, JSON.parse(d.data));
				});
			}

			return;
		}

		if (type === 'current') {
			if (search !== '') {
				API.search($scope.lang, $scope.vendor, $scope.bundle, search).then(function(d) {
					setTranslations(type, offset, size, d.data);
				});
			} else {
				API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
					setTranslations(type, offset, size, d.data);
				}); 
			}
		} 

		if (type === 'prefered') {
			if ($scope.search !== '') {
				API.search($scope.preferedLang, $scope.vendor, $scope.bundle, search).then(function(d) {
					setTranslations(type, offset, size, d.data);
				}); 
			} else {
				API.load($scope.preferedLang, $scope.vendor, $scope.bundle).then(function(d) {
					setTranslations(type, offset, size, d.data);
				}); 
			}		
		}
	}
	
	loadTranslations('current');  
	loadTranslations('prefered'); 

	$scope.setPreferedLang = function(lang) {
		$scope.preferedLang = lang;
		loadTranslations('prefered');
	}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              

	$scope.setLang = function(lang) {
		$scope.lang = lang;
		loadTranslations('current');
	} 

	$scope.setRepository = function(repository) {
		for (var key in repository) {
		    if (repository.hasOwnProperty(key)) {
		    	$scope.vendor = key;
		    	$scope.bundle = repository[key];
		    	$scope.repository = $scope.vendor + $scope.bundle;
		    	loadTranslations('current');
		    	loadTranslations('prefered');
		    }
		}
	}

	$scope.addTranslation = function(cell, row, col) {
		var data = {
			'translation_item': row.id, 
			'translation': row.translations[0].translation
		};

		cacheRow(row);

		promise = $http.post(
			Routing.generate('claroline_translator_add_translation'),
			data
		);

	 	var alertSuccess = '<div class="alert alert-success">' + 
	 		'<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' +
		 	 window.Translator.trans('new_translation', {translation: row.translations[0].translation}, 'translator')
		'</div>';

		promise.then(function(d) {
			var myEl = angular.element(document.querySelector('#translator-panel'));
			myEl.prepend(alertSuccess);  
		});
	}

	$scope.loadInfos = function(row) {
		API.translationsInfo($scope.lang, row.vendor, row.bundle, row.domain, row.key).then(function(d) {
			$scope.translationInfos[row.id] = d.data;
		});
	}

	$scope.clickAdminLock = function(row) {
		API.clickAdminLock(row.id).then(function(d) {
			row.admin_lock = !row.admin_lock;
			cacheRow(row);
		});
	}

	$scope.clickUserLock = function(row) {
		API.clickUserLock(row.id).then(function(d) {
			row.user_lock = !row.user_lock;
			cacheRow(row);
		});
	}

	$scope.paging = function(offset, size) {
		loadTranslations('current');
		loadTranslations('prefered');
	}

	$scope.getPreferedTranslation = function(cell, row, col) {
		var idx = $scope.translations.indexOf(row);

		return (typeof $scope.preferedTranslations[idx] !== 'undefined') ?
			$scope.preferedTranslations[idx].translations[0].translation:
			'not found';
	}

	$scope.loadTranslations = function() {
		loadTranslations('current');
		loadTranslations('prefered');
	}

	$scope.comment = function(row) {
		API.comment(row.id).then(function(d) {
			var route = Routing.generate('claro_forum_messages', {'subject': d.data.subject_id});
			window.location.href = route;
		});
	}
});

gitTranslator.factory('API', function($http) {
	var api = {};

	api.load = function(lang, vendor, bundle) {
		return $http.get(Routing.generate(
			'claroline_translator_get_latest', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang}
		));
	}

	api.search = function(lang, vendor, bundle, search) {
		return $http.get(Routing.generate(
			'claroline_translator_search_latest',
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'search': search}
		));
	}

	api.locales = function() {
		return $http.get(Routing.generate('claroline_translator_langs'));
	}

	api.repositories = function() {
		return $http.get(Routing.generate('claroline_translator_repositories'));
	}

	api.translationsInfo = function(lang, vendor, bundle, domain, key) {
		return $http.get(Routing.generate(
			'claroline_translator_get_translation_info', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'key': key, 'domain': domain}
		));
	}

	api.clickUserLock = function(id) {
		return $http.post(Routing.generate(
			'claroline_translator_user_lock', 
			{'translationItem': id}
		));
	}

	api.clickAdminLock = function(id) {
		return $http.post(Routing.generate(
			'claroline_translator_admin_lock', 
			{'translationItem': id}
		));
	}

	api.comment = function(id) {
		return $http.get(Routing.generate(
			'claroline_translator_forum_subject',
			{'translationItem': id}
		));
	}

	return api;
});

angular.module('gitTranslator').directive('locales', [
    function locales() {
        return {
        	restrict: 'E',
            templateUrl: AngularApp.webDir + 'bundles/clarolinetranslator/js/views/langs.html',
            replace: false
        };
    }
]);

angular.module('gitTranslator').directive('repositories', [
    function repositories() {
        return {
        	restrict: 'E',
            templateUrl: AngularApp.webDir + 'bundles/clarolinetranslator/js/views/repositories.html',
            replace: false
        };
    }
]);

angular.module('gitTranslator').directive('translations', [
	function translations() {
		return {
			restrict: 'E',
			templateUrl: AngularApp.webDir + 'bundles/clarolinetranslator/js/views/translations.html',
            replace: false
		}
	}
]);

angular.module('gitTranslator').directive('preferedlang', [
    function preferedlang() {
        return {
        	restrict: 'E',
            templateUrl: AngularApp.webDir + 'bundles/clarolinetranslator/js/views/prefered.html',
            replace: false
        };
    }
]);

angular.module('gitTranslator').directive('searchbar', [
    function searchbar() {
        return {
        	restrict: 'E',
            templateUrl: AngularApp.webDir + 'bundles/clarolinetranslator/js/views/searchbar.html',
            replace: false
        };
    }
]);