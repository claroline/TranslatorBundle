var gitTranslator = angular.module('gitTranslator', ['data-table']);

//let's do some initialization first.
gitTranslator.config(function ($httpProvider) {
	var stackedRequests = 0;

	$httpProvider.interceptors.push(function ($q) {
		return {
			'request': function(config) {
				stackedRequests++;
				$('.please-wait').show();

				return config;
			},
			'response': function(response) {
				stackedRequests--;
				$('.please-wait').hide();

				return response;
			}
		};
	});
});

gitTranslator.controller('contentCtrl', function($scope, $log, $http, $cacheFactory, API) {
	//$http.defaults.cache        = true;
	$scope.lang                 = 'fr';
	$scope.bundle               = 'CoreBundle';
	$scope.vendor               = 'claroline';
	$scope.repository           = 'clarolineCoreBundle';
	$scope.$log                 = $log;
	$scope.$langs               = ['fr', 'en'];
	$scope.translations         = [];
	$scope.preferedLang         = 'fr';
	$scope.preferedTranslations = [];
	$scope.translationInfos     = '';
	$scope.infos                = '';
	$scope.paged                = [];

 	$scope.dataTableOptions = {
 		scrollbarV: false,
 		columnMode: 'force',
 		rowHeight: 50,
        headerHeight: 50,
        footerHeight: 50,
 		paging: {
 			externalPaging: true,
 			size: 10
 		}
 	}

	API.repositories().then(function(d) {
		$scope.repositories = d.data;
	});

	API.locales().then(function(d) {
		$scope.langs = d.data;
	});    

	var loadTranslations = function(type) {
		if (type === 'prefered' || type === 'all') {
			API.load($scope.lang, $scope.vendor, $scope.bundle, $scope.currentPage).then(function(d) {
				$scope.preferedTranslations = d.data;
			}); 
		}

		if (type === 'current' || type === 'all') {
			API.load($scope.lang, $scope.vendor, $scope.bundle, $scope.currentPage).then(function(d) {
				$scope.paged = $scope.translations = d.data;
				$scope.dataTableOptions.paging.count = d.data.length
			}); 
		}
	}

	var findById = function(id) {
		for (var i = 0; i < $scope.translations.length; i++) {
			console.log($scope.translations[i].id);
			if ($scope.translations[i].id == id) return $scope.translations[i];
		}
	}
	
	loadTranslations('all');   

	$scope.setPreferedLang = function(lang) {
		$scope.preferedLang = lang;
		loadTranslations('prefered')
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
		    }
		}
	}

	$scope.addTranslation = function(id, translation) {
		var element = findById(id);

		var data = {
			'key': element.key, 
			'bundle': element.bundle, 
			'vendor': element.vendor, 
			'domain': element.domain,
			'translation': element.translation,
			'lang': $scope.lang
		};

		promise = $http.post(
			Routing.generate('claroline_translator_add_translation'),
			data
		);

		/* DataTable doesn't seem to use the cache...
		var httpCache = $cacheFactory.get('$http');
		var cacheKey = Routing.generate(
			'claroline_translator_get_latest', 
			{'vendor': element.bundle, 'bundle': element.bundle, 'lang': $scope.lang, 'page': $scope.page }
		);
		var cachedResponse = httpCache.put(cacheKey, $scope.translations);

		promise.then(function(d) {
			reload from cache
			loadTranslations('current');
		});
		*/
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

		$scope.paged = $scope.translations.splice(offset * size, $scope.dataTableOptions.paging.count);
		//why the fuck am I supposed to do this wizzardry ?
		var pos = [offset * size, 0];
		$scope.translations.splice.apply(
			$scope.translations,
			pos.concat($scope.paged)
		);
	}
});

gitTranslator.factory('API', function($http) {
	var api = {};

	api.load = function(lang, vendor, bundle, page) {
		return $http.get(Routing.generate(
			'claroline_translator_get_latest', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'page': page
		}
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
			{'vendor': vendor, 'bundle': bundle, 'lang': lang, 'key': key}
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