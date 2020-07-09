class Duration {
	constructor() {
		this.start = new Date().getTime();
	};

	end() {
		return new Date().getTime() - this.start;
	};

	prettyEnd() {
		var ret = "";

		const ms2h = 1 / (60 * 60 * 1000);
		const ms2m = 1 / (60 * 1000);
		const ms2s = 1 / (1000);

		var ms = this.end();
		var h = Math.floor(ms * ms2h);
		ms -= h / ms2h;
		var m = Math.floor(ms * ms2m);
		ms -= m / ms2m;
		var s = Math.floor(ms * ms2s);
		ms -= s / ms2s;

		ret += (h > 0) ? (h + "h") : ("");
		ret += ((ret.length > 0) ? (" ") : ("")) + ((m > 0 || ret.length) ? (m + "m") : (""));
		ret += ((ret.length > 0) ? (" ") : ("")) + ((s > 0 || ret.length) ? (s + "s") : (""));
		ret += ((ret.length > 0) ? (" ") : ("")) + ms + "ms";
		return ret;
	};
};
