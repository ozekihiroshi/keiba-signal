#!/usr/bin/env bash
set -euo pipefail

KEEP_PROJECTS=("demand-monitor" "keiba-signal")
KEEP_NETWORKS=("traefik_network" "bridge" "host" "none")

echo "== Down (no volumes) =="
( cd /home/ubuntu/docker/demand-monitor && docker compose down --remove-orphans )
( cd /home/ubuntu/docker/keiba-signal 2>/dev/null && docker compose down --remove-orphans ) || true

echo "== Remove exited/dead containers =="
docker ps -aq -f status=exited -f status=dead | xargs -r docker rm

echo "== Remove dangling images =="
docker images -q -f dangling=true | xargs -r docker rmi

echo "== Remove dangling volumes except kept projects =="
vols=$(docker volume ls -qf dangling=true || true)
for v in $vols; do
  lbl=$(docker volume inspect "$v" --format '{{json .Labels}}')
  keep=0
  for p in "${KEEP_PROJECTS[@]}"; do
    grep -q "com.docker.compose.project\":\"$p\"" <<<"$lbl" && keep=1 && break
  done
  if [ $keep -eq 0 ]; then
    echo "remove volume: $v"
    docker volume rm "$v" || true
  fi
done

echo "== Remove unused custom networks (keep traefik) =="
for n in $(docker network ls --filter type=custom --format '{{.Name}}'); do
  skip=0; for k in "${KEEP_NETWORKS[@]}"; do [[ "$n" == "$k" ]] && skip=1; done
  if [ $skip -eq 0 ]; then docker network rm "$n" || true; fi
done

echo "== Prune build cache =="
docker builder prune -f

echo "== Done. Current usage =="
docker system df
#!/usr/bin/env bash
set -euo pipefail

KEEP_PROJECTS=("demand-monitor" "keiba-signal")
KEEP_NETWORKS=("traefik_network" "bridge" "host" "none")

echo "== Down (no volumes) =="
( cd /home/ubuntu/docker/demand-monitor && docker compose down --remove-orphans )
( cd /home/ubuntu/docker/keiba-signal 2>/dev/null && docker compose down --remove-orphans ) || true

echo "== Remove exited/dead containers =="
docker ps -aq -f status=exited -f status=dead | xargs -r docker rm

echo "== Remove dangling images =="
docker images -q -f dangling=true | xargs -r docker rmi

echo "== Remove dangling volumes except kept projects =="
vols=$(docker volume ls -qf dangling=true || true)
for v in $vols; do
  lbl=$(docker volume inspect "$v" --format '{{json .Labels}}')
  keep=0
  for p in "${KEEP_PROJECTS[@]}"; do
    grep -q "com.docker.compose.project\":\"$p\"" <<<"$lbl" && keep=1 && break
  done
  if [ $keep -eq 0 ]; then
    echo "remove volume: $v"
    docker volume rm "$v" || true
  fi
done

echo "== Remove unused custom networks (keep traefik) =="
for n in $(docker network ls --filter type=custom --format '{{.Name}}'); do
  skip=0; for k in "${KEEP_NETWORKS[@]}"; do [[ "$n" == "$k" ]] && skip=1; done
  if [ $skip -eq 0 ]; then docker network rm "$n" || true; fi
done

echo "== Prune build cache =="
docker builder prune -f

echo "== Done. Current usage =="
docker system df

