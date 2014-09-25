/*
Phoenix2
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
This is the javascript framework providing the PH2Controller functionality. All Components are bound to the Namespace PH2Controller.
===
Usage: 
$(document).ready( function() {
		#TODO
});
*/

//DEFAULT ROUTINE for all PH2Controllers
// (none)

var PH2Controller = {
	
	//Controller Object
	Search : function() {
	/* Searches for Types/Occurrences in the Database */
		
		//PRIVATE
		var _tokens;
		var _tokentypes;
		var _lemmata;
		var _lemmatypes;
		var _status = 0; //1: ready, 0: wait (loading data)
		var _connected_components = []; //components which receive data from this controller
		
		//Private methods
		var _set_status = function (status) {
			// puts all connected components into waiting mode when this controller is loading data
			if (status==1 || status==0) {
				_status = status;
				for (var i in _connected_components) {
					_connected_components[i].set_status(status);
				}
			}
		}
		
		var _init_data = function () {
			
			_set_status(0);
			var substatus_types = 0;
			var substatus_lemmata = 0;
			
			// get token types
			$.getJSON('actions/php/ajax.php?action=getTokentypes', function(data_tokentypes) {
				_tokentypes = data_tokentypes;
			})
			.success( function() {
				
				// get tokens
				$.getJSON('actions/php/ajax.php?action=getTokens', function(data_tokens) {
					_tokens = data_tokens;
				})
				.success( function() {
					substatus_types = 1;
					if (substatus_lemmata) {
						_set_status(1);
					}
				});
			
			});
			
			// get lemma types
			$.getJSON('actions/php/ajax.php?action=getLemmatypes', function(data_lemmatypes) {
				_lemmatypes = data_lemmatypes;
			})
			.success( function() {
				
				// get lemmata
				// CAUTION: similar function below (public)
				$.getJSON('actions/php/ajax.php?action=getLemmata', function(data_lemmata) {
					_lemmata = data_lemmata;
				})
				.success( function() {
					substatus_lemmata = 1;
					if (substatus_types) {
						_set_status(1);
					}
				});
			
			});
			
			
		}
		
		var _clear_results = function () {
			for (var i in _connected_components) {
				_connected_components[i].clear();
			}
		}
		
		
		//Default routine (constructor)	
		_init_data();
		
		
		
		//PUBLIC
		return {
			// takes a regular expression and returns a list of all matching types (TOKEN) with their TokenID
			// @param tokentypes (array) defines which token types are taken into account
			find : function (mode, regex, typeIDs) {
				var items;
				if (mode == 'TYPE') {
					items = _tokens
				} else if (mode == 'LEMMA') {
					items = _lemmata;
				}
				
				try {
					regex = new RegExp(regex);
					var result = [];
					for (var i in typeIDs) {
						var id = typeIDs[i];
						for (var k in items[id]) {
							var tokenID = items[id][k][0];
							var surface = items[id][k][1];
							//alert(tokenID + ' -> ' + surface);
							if (regex.test(surface)) {
								result.push([tokenID, surface]);
							}
						}
					}
					//alert(result);
					return(result);
				}
				catch (err) {
					alert('Invalid Regular Expression\n' + err);
				}
			},
			
			getTypes : function ( mode ) {
				if (mode == 'TYPE') {
					return _tokentypes;
				} else if (mode == 'LEMMA') {
					return _lemmatypes;
				}
			},
			
			connect_component : function(c) {
				_connected_components.push(c);
			},
			
			clear_results : function () {
				_clear_results();
			},
			
			init : function () {
				_init_data();
			},
			
			refresh_lemmata : function () {
				//reloads the list of lemmata from the database
				// get lemmata
				$.getJSON('actions/php/ajax.php?action=getLemmata', function(data_lemmata) {
					_lemmata = data_lemmata;
				});
			}
					
		}
	
	}
	
}