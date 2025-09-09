#!/usr/bin/env bash

notice () {
  echo
  echo '----------------------------------------'
  echo $@
  echo '----------------------------------------'
}

for doctrine_orm_ver in "^2.20" "3.0.*" "^3.0"; do
  notice "Check with Doctrine ORM ${doctrine_orm_ver}"
  composer update --with "doctrine/orm:${doctrine_orm_ver}" --with-all-dependencies && \
  composer run checks || exit $?
done

for doctrine_dbal_ver in "3.2.*" "^3.2" "4.0.*" "^4"; do
  notice "Check with Doctrine DBAL ${doctrine_dbal_ver}"
  composer update --with "doctrine/dbal:${doctrine_dbal_ver}" --with-all-dependencies && \
  composer run checks || exit $?
done

for doctrine_persistence_ver in "3.0.*" "^3" "4.0.*" "^4"; do
  notice "Check with Doctrine Persistence ${doctrine_persistence_ver}"
  composer update --with "doctrine/persistence:${doctrine_persistence_ver}" --with-all-dependencies && \
  composer run checks || exit $?
done

for symfony_ver in "^6.4" "7.0.*" "7.1.*" "7.2.*" "^7.3"; do
  notice "Check with Symfony ${symfony_ver}"
  composer update \
    --with "symfony/config:${symfony_ver}" \
    --with "symfony/console:${symfony_ver}" \
    --with "symfony/dependency-injection:${symfony_ver}" \
    --with "symfony/http-kernel:${symfony_ver}" \
    --with "symfony/cache:${symfony_ver}" \
    --with-all-dependencies && \
  composer run checks || exit $?
done
