class Duration {
	constructor() {
		this.start = new Date().getTime();
	};

	end() {
		return new Date().getTime() - this.start;
	};

	prettyEnd() {
		var ms = this.end();
		return prettyDuration(ms);
	};
};

