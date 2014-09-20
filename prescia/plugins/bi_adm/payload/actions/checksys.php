<?

	$this->layout = 2;
	if (CONS_ONSERVER && (is_file("maint.txt") || is_file("heavymaint.html"))) echo "n";
	else echo "y";

	$core->close();