!function() {
	function __resize() {
		// Layout is handled responsively in CSS; keep this hook for future orientation logic.
	}
	__resize();
	window.addEventListener('resize', __resize, false);
	
	let id = HUHUSdk.getUrlParam("id");
	var game = HUHUSdk.getGameById(id);
	document.title = game.name;
	
	let iframe_game = document.getElementById('iframe_game');
    iframe_game.src = HUHUSdk.getGameUrl(game.url);
    iframe_game.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });		
    let div_iframe_game = document.getElementById('div_iframe_game');
    div_iframe_game.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });

	window.onmessage = function (e) {
		e = e || event;
		tempData = e.data + "";
		if(tempData == "open" || tempData == "showInterstitial"){
			document.getElementById("iframe_game").contentWindow.postMessage("close","*");
		}
		else if(tempData == "showReward"){
			document.getElementById("iframe_game").contentWindow.postMessage("close","*");
		}
	}
}(window)
