# Thesis Mapper
This maps theses based on their ID from old islandora URL to DSpace Handle

## HOWTO

```
> kubectl get pods --namespace=prod | grep scholar-solr

unbscholar-solr-lib-unb-ca-78688bbcb-7z95r                       1/1     Running            0               13d
```

```
> kubectl port-forward unbscholar-solr-lib-unb-ca-78688bbcb-7z95r 8983:8983 --namespace=prod

Forwarding from 127.0.0.1:8983 -> 8983
Forwarding from [::1]:8983 -> 8983
```

```
> php -f map.php
```
