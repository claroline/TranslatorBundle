var gitTranslator = angular.module('gitTranslator', []);

gitTranslator.controller('contentCtrl', function($scope, $log, API) {
	$scope.lang         = 'fr';
	$scope.bundle       = 'CoreBundle';
	$scope.vendor       = 'claroline';
	$scope.$log         = $log;
	$scope.$langs       = ['fr', 'en'];
	$scope.translations = [];
	$scope.preferedLang = 'fr';
  
	API.locales().then(function(d) {
		$scope.langs = d.data;
	});    

	API.repositories().then(function(d) {
		$scope.repositories = d.data;
	});

	API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
		$scope.translations = d.data;
		console.log($scope.translations);
	});                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  

	$scope.setLang = function(lang) {
		$scope.lang = lang;
		API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
			$scope.translations = d.data;
			console.log($scope.translations);
		}); 
	} 

	$scope.setRepository = function(vendor, bundle) {
		$scope.bundle = bundle;
		$scope.vendor = vendor;

		API.load($scope.lang, $scope.vendor, $scope.bundle).then(function(d) {
			$scope.translations = d.data;
			console.log($scope.translations);
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