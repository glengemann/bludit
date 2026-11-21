.PHONY: trivy

trivy:
	docker compose run --rm trivy fs --scanners vuln,misconfig,secret --skip-dirs /project/poc --skip-dirs /project/tools /project
