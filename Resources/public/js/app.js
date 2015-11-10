var gitTranslator = angular.module('gitTranslator', []);

gitTranslator.controller('contentCtrl', function($scope, $log, $http, API) {
	//todo: add cache system
	$scope.lang             = 'fr';
	$scope.bundle           = 'CoreBundle';
	$scope.vendor           = 'claroline';
	$scope.reposirory       = 'clarolineCoreBundle';
	$scope.$log             = $log;
	$scope.$langs           = ['fr', 'en'];
	$scope.translations     = [];
	$scope.preferedLang     = 'fr';
	//cached for translations info
	$scope.translationsInfo = [];
  
	API.locales().then(function(d) {
		$scope.langs = d.data;
	});    

	API.repositories().then(function(d) {
		$scope.repositories = d.data;
	});

	//current translations
	API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
		$scope.translations = d.data;
	});                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  

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

		API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
			$scope.translations = d.data;
		}); 
	}

	$scope.getTranslationsInfos = function(key) {
		API.translationsInfo($scope.lang, $scope.vendor, $scope.bundle, key).then(function(d) {
			$scope.translationsInfo[$scope.repository][$scope.lang] = d.data;
		});
	}

	$scope.addTranslation = function(index, vendor, bundle, domain, key) {
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

		/*.success(function() {
			alert ('yeah !');
		}).failure(function() {
			alert('FUCK NO');
		});*/
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
			'claroline_translator_get_latest', 
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