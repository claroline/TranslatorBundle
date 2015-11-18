var gitTranslator = angular.module('gitTranslator', ['data-table']);

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
	API
) {
	$http.defaults.cache        = true;
	$scope.lang                 = 'fr';
	$scope.bundle               = 'CoreBundle';
	$scope.vendor               = 'claroline';
	$scope.repository           = 'clarolineCoreBundle';
	$scope.$log                 = $log;
	$scope.$langs               = ['fr', 'en'];
	$scope.translations         = [];
	$scope.preferedLang         = 'fr';
	$scope.preferedTranslations = [];
	$scope.search               = '';

 	$scope.dataTableOptions = {
 		scrollbarV: false,
 		columnMode: 'force',
 		rowHeight: 50,
        headerHeight: 50,
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

	var getCurrentCacheKey = function() {
		return $scope.search === '' ?
			Routing.generate(
				'claroline_translator_get_latest', 
				{'vendor': $scope.vendor, 'bundle': $scope.bundle, 'lang': $scope.lang}
			):
			Routing.generate(
				'claroline_translator_search_latest', 
				{'vendor': $scope.vendor, 'bundle': $scope.bundle, 'lang': $scope.lang, 'search': $scope.search}
			);
	}

	var setTranslations = function(offset, size, translations) {
  		var cache = translations;
  		var cache = JSON.parse(JSON.stringify(translations));

		$scope.translations = translations;
		$scope.dataTableOptions.paging.count = translations.length;

		var set = translations.splice(offset * size, size);
        // only insert items i don't already have
          set.forEach(function(r, i) {
            var idx = i + (offset * size);
            $scope.translations[idx] = r;
        });

        var httpCache = $cacheFactory.get('$http');
        var cachedResponse = httpCache.put(getCurrentCacheKey(), cache);
	}

	var loadTranslations = function() {
		var offset = $scope.dataTableOptions.paging.offset;
		var size = $scope.dataTableOptions.paging.size;	
		var search = $scope.search;

		var httpCache = $cacheFactory.get('$http');
		var fromCache = httpCache.get(getCurrentCacheKey());

		//angular cache system is wtf
		if (fromCache) {
			if (fromCache instanceof Array) {
				if (fromCache[3] === "OK") {
					var parsed = JSON.parse(fromCache[1]);
				} else {
					var parsed = fromCache;
				}
				setTranslations(offset, size, parsed);
			} else {
				fromCache.then(function(d) {
					setTranslations(offset, size, JSON.parse(d.data));
				});
			}
		}

		if (search !== '') {
			API.search($scope.lang, $scope.vendor, $scope.bundle, search).then(function(d) {
				setTranslations(offset, size, d.data);
			});
		} else {
			API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
				setTranslations(offset, size, d.data);
			}); 

		}

		console.log($scope.search);
		if ($scope.search !== '') {
			API.search($scope.preferedLang, $scope.vendor, $scope.bundle, search).then(function(d) {
				$scope.preferedTranslations = d.data;

				if ($scope.search !== '') {
					var set = d.data.splice(offset * size, size);
			        // only insert items i don't already have
			          set.forEach(function(r, i){
			            var idx = i + (offset * size);
			            $scope.preferedTranslations[idx] = r;
			        });
				}
			}); 
		} else {
			API.load($scope.preferedLang, $scope.vendor, $scope.bundle).then(function(d) {
				$scope.preferedTranslations = d.data;
			}); 
		}

	}
	
	loadTranslations();   

	$scope.setPreferedLang = function(lang) {
		$scope.preferedLang = lang;
		loadTranslations();
	}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              

	$scope.setLang = function(lang) {
		$scope.lang = lang;
		loadTranslations();
	} 

	$scope.setRepository = function(repository) {
		for (var key in repository) {
		    if (repository.hasOwnProperty(key)) {
		    	$scope.vendor = key;
		    	$scope.bundle = repository[key];
		    	$scope.repository = $scope.vendor + $scope.bundle;
		    	loadTranslations();
		    }
		}
	}

	$scope.addTranslation = function(cell, row, col) {
		var data = {
			'key': row.key, 
			'bundle': row.bundle, 
			'vendor': row.vendor, 
			'domain': row.domain,
			'translation': row.translation,
			'lang': $scope.lang
		};

		//This stupid plugin needs the array splice function and truncate every results.
		//Because of the this the cache handling is... let's say annoying.
		var httpCache = $cacheFactory.get('$http');
		var fromCache = httpCache.get(getCurrentCacheKey());
		var parsed = fromCache;

		for (var i = 0; i < parsed.length; i++) {
			if (parsed[i].id === row.id) parsed[i] = row;
		}

		var cachedResponse = httpCache.put(getCurrentCacheKey(), parsed);

		promise = $http.post(
			Routing.generate('claroline_translator_add_translation'),
			data
		);
	}

	$scope.translationInfo = function(index) {
		var element = $scope.translations[index];
		$scope.infos = '';

		API.translationsInfo($scope.lang, element.vendor, element.bundle, element.key).then(function(d) {
			$scope.translationInfos = d.data;
			for (var i = 0; i < $scope.translationInfos.length; i++) {
				$scope.infos += $scope.translationInfos[i].translation;
			}
		});
	}

	$scope.clickAdminLock = function(index) {
		var element = $scope.translations[index];

		api.clickUserLock($scope.lang, element.vendor, element.bundle, element.key).then(function(d) {
			console.log('user lock');
		});
	}

	$scope.clickUserLock = function(index) {
		var element = $scope.translations[index];

		api.clickUserLock($scope.lang, element.vendor, element.bundle, element.key).then(function(d) {
			console.log('admin lock');
		});
	}

	$scope.paging = function(offset, size) {
		loadTranslations(offset, size, $scope.search);
	}

	$scope.getPreferedTranslation = function(cell, row, col) {
		var idx = $scope.translations.indexOf(row);

		return (typeof $scope.preferedTranslations[idx] !== 'undefined') ?
			$scope.preferedTranslations[idx].translation:
			'not found';
	}

	$scope.loadTranslations = function() {
		loadTranslations();
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

	api.translationsInfo = function(lang, vendor, bundle, key) {
		return $http.get(Routing.generate(
			'claroline_translator_get_translation_info', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'key': key}
		));
	}

	api.clickUserLock = function(lang, vendor, bundle, key) {
		return $http.get(Routing.generate(
			'claroline_translator_user_lock', 
			{'vendor': $scope.vendor, 'bundle': $scope.bundle, 'lang': lang, 'key': key}
		));
	}

	api.clickAdminLock = function(lang, vendor, bundle, key) {
		return $http.get(Routing.generate(
			'claroline_translator_admin_lock', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'key': key}
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