docs:
	mkdir -p "docs"
	apigen \
	--source ./../common/ \
	--source ./../prototype/ \
	--source ./ \
	--destination docs/ --title ICanBoogie/ActiveRecord \
	--exclude "*/build/*" \
	--exclude "*/tests/*" \
	--template-config /usr/share/php/data/ApiGen/templates/bootstrap/config.neon
