var gitTranslator = angular.module('gitTranslator', ['ui.bootstrap']);

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
	$scope.translationInfos     = '';
	$scope.infos                = '';

	API.repositories().then(function(d) {
		$scope.repositories = d.data;
	});

	API.locales().then(function(d) {
		$scope.langs = d.data;
	});    

	//current translations
	API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
		$scope.preferedTranslations = $scope.translations = d.data;
	});    

	$scope.setPreferedLang = function(lang) {
		$scope.preferedLang = lang;
		API.load(lang, $scope.vendor, $scope.bundle).then(function(d) {
			$scope.preferedTranslations = d.data;
		}); 

	}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              

	$scope.setLang = function(lang) {
		$scope.lang = lang;
		API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
			$scope.translations = d.data;
		}); 
	} 

	$scope.setRepository = function(repository) {
		for (var key in repository) {
		    if (repository.hasOwnProperty(key)) {
		    	$scope.vendor = key;
		    	$scope.bundle = repository[key];
		    	$scope.repository = $scope.vendor + $scope.bundle;
		    }
		}
	}

	$scope.addTranslation = function(index) {
		var element = $scope.translations[index];

		var data = {
			'key': element.key, 
			'bundle': element.bundle, 
			'vendor': element.vendor, 
			'domain': element.domain,
			'translation': element.translation,
			'lang': $scope.lang
		};

		$http.post(
			Routing.generate('claroline_translator_add_translation'),
			data
		);

		var httpCache = $cacheFactory.get('$http');
		var cacheKey = Routing.generate(
			'claroline_translator_get_latest', 
			{'vendor': element.bundle, 'bundle': element.bundle, 'lang': $scope.lang}
		);
		var cachedResponse = httpCache.put(cacheKey, $scope.translations);
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
});

gitTranslator.factory('API', function($http) {
	var api = {};

	api.load = function(lang, vendor, bundle) {
		return $http.get(Routing.generate(
			'claroline_translator_get_latest', 
			{'vendor': vendor, 'bundle': bundle, 'lang': lang}
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