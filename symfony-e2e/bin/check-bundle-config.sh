#!/usr/bin/env bash

# Validates the bundle configuration against the current set of installed
# packages to ensure all enabled features have their required dependencies.

PROJECT_DIR="$(dirname "$0")/../"
cd "$PROJECT_DIR" || exit 1

###############################
# START Functions for testing #
###############################
get_tests() {
    declare -F | awk '{print $3}' | grep '^test_'

    return 0
}

before_test() {
    return 0
}

after_test() {
    return 0
}

run_tests() {
    local TESTS_COUNT=0
    local TESTS_SUCCESS_COUNT=0
    local TESTS_FAIL_COUNT=0

    echo 'Run tests:'

    for single_test in $(get_tests); do
        TESTS_COUNT=$((TESTS_COUNT + 1))

        {
            before_test &&
            "$single_test"
        } && {
            echo "✓ $single_test"
            TESTS_SUCCESS_COUNT=$((TESTS_SUCCESS_COUNT + 1))
        } || {
            echo "× $single_test"
            TESTS_FAIL_COUNT=$((TESTS_FAIL_COUNT + 1))
        }

        after_test
    done

    if [ "$TESTS_COUNT" -eq 0 ]; then
        echo 'No tests' > /dev/stderr;
        return 1
    fi

    echo
    echo "Total:   ${TESTS_COUNT}"
    echo "Success: ${TESTS_SUCCESS_COUNT}"
    echo "Fail:    ${TESTS_FAIL_COUNT}"

    if [ "$TESTS_FAIL_COUNT" -eq 0 ]; then
        return 0;
    else
        return 1;
    fi
}

#############################
# END Functions for testing #
#############################

##########################
# START Common Functions #
##########################
check_no_code_changes() {
    EXCLUDE_FILE="$(realpath --relative-to=. "$0")"

    if git status --porcelain . | grep -v "$EXCLUDE_FILE" | grep -q .; then
        echo 'There are uncommitted changes.' > /dev/stderr
        return 1
    fi
}
restore_composer_dependencies() {
    {
        TMP=$(mktemp)

        # 1. Save current file to temp file before
        # 2. Restore, using git reset hard
        # 3. Restore current file
        rm "$TMP" && \
        cp "${0}" "$TMP" && \
        git add . && \
        git reset --hard HEAD && \
        cat "$TMP" > "${0}" && \
        rm "${TMP}" && \
        composer install
    } > /dev/null 2>&1
}

get_config() {
    bin/console debug:config --format yaml trollbus
}

########################
# END Common Functions #
########################

#######################
# START Declare tests #
#######################
before_test() {
    restore_composer_dependencies
}

test_01_default() {
    local EXPECTED=$(cat <<-EOF
trollbus:
    created_at:
        enabled: true
        clock: clock
    logger:
        enabled: true
        logger: logger
    message_id:
        enabled: true
        generator: Trollbus\TrollbusBundle\MessageId\SymfonyUidMessageIdGenerator
    transaction:
        enabled: true
        transaction_provider: Trollbus\DoctrineORMBridge\Transaction\DoctrineTransactionProvider
    entity_handler:
        enabled: true
        entity_finder: Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntityFinder
        entity_saver: Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntitySaver
        criteria_resolver: Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver
        classes: {}
    doctrine_orm_bridge:
        enabled: true
        manager_registry: doctrine
        manager: null
        entity_saver_flush: true
        flusher: true

EOF
)
    local ACTUAL="$(get_config)"

    test "$EXPECTED" == "$ACTUAL"
}

test_02_without_doctrine_orm_bridge() {
    composer remove trollbus/doctrine-orm-bridge > /dev/null 2>&1 || return 1

    local EXPECTED=$(cat <<-EOF
trollbus:
    created_at:
        enabled: true
        clock: clock
    logger:
        enabled: true
        logger: logger
    message_id:
        enabled: true
        generator: Trollbus\TrollbusBundle\MessageId\SymfonyUidMessageIdGenerator
    transaction:
        enabled: false
    entity_handler:
        enabled: false
        criteria_resolver: Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver
        classes: {}

EOF
    )
    local ACTUAL="$(get_config)"

    test "$EXPECTED" == "$ACTUAL"
}

test_03_without_symfony_clock() {
    composer remove symfony/clock > /dev/null 2>&1 || return 1

    local EXPECTED=$(cat <<-EOF
trollbus:
    created_at:
        enabled: true
        clock: null
    logger:
        enabled: true
        logger: logger
    message_id:
        enabled: true
        generator: Trollbus\TrollbusBundle\MessageId\SymfonyUidMessageIdGenerator
    transaction:
        enabled: true
        transaction_provider: Trollbus\DoctrineORMBridge\Transaction\DoctrineTransactionProvider
    entity_handler:
        enabled: true
        entity_finder: Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntityFinder
        entity_saver: Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntitySaver
        criteria_resolver: Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver
        classes: {}
    doctrine_orm_bridge:
        enabled: true
        manager_registry: doctrine
        manager: null
        entity_saver_flush: true
        flusher: true

EOF
)
    local ACTUAL="$(get_config)"

    test "$EXPECTED" == "$ACTUAL"
}

test_04_without_symfony_uid() {
    composer remove symfony/uid > /dev/null 2>&1 || return 1

    local EXPECTED=$(cat <<-EOF
trollbus:
    created_at:
        enabled: true
        clock: clock
    logger:
        enabled: true
        logger: logger
    message_id:
        enabled: true
        generator: Trollbus\MessageBus\MessageId\RandomMessageIdGenerator
    transaction:
        enabled: true
        transaction_provider: Trollbus\DoctrineORMBridge\Transaction\DoctrineTransactionProvider
    entity_handler:
        enabled: true
        entity_finder: Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntityFinder
        entity_saver: Trollbus\DoctrineORMBridge\EntityHandler\DoctrineEntitySaver
        criteria_resolver: Trollbus\MessageBus\EntityHandler\PropertyCriteriaResolver
        classes: {}
    doctrine_orm_bridge:
        enabled: true
        manager_registry: doctrine
        manager: null
        entity_saver_flush: true
        flusher: true

EOF
)
    local ACTUAL="$(get_config)"

    test "$EXPECTED" == "$ACTUAL"
}

test_05_register_handlers() {
    bin/console cache:clear > /dev/null 2>&1 || return 1

    local EXPECTED=$(cat <<-EOF

Symfony Container Services Tagged with "trollbus.handler" Tag
=============================================================

 ------------------------------------------- ------------------------------------------------ --------------- -------------------------------------------------------- ------------------------ -------------------------------------------------------
  Service ID                                  message                                          handlerType     handlerClass                                             handlerMethod            Class name
 ------------------------------------------- ------------------------------------------------ --------------- -------------------------------------------------------- ------------------------ -------------------------------------------------------
  trollbus.handler.0                          App\EntityHandler\Attribute\Event\PostCreated    callable        App\EntityHandler\Attribute\EventListener\PostListener   onPostCreated            Trollbus\MessageBus\Handler\CallableHandler
  trollbus.handler.1                          App\EntityHandler\Attribute\Event\PostUpdated    callable        App\EntityHandler\Attribute\EventListener\PostListener   onPostUpdated            Trollbus\MessageBus\Handler\CallableHandler
  trollbus.handler.2                          App\EntityHandler\Attribute\Event\PostCreated    callable        App\EntityHandler\Attribute\EventListener\PostListener   onPostCreatedOrUpdated   Trollbus\MessageBus\Handler\CallableHandler
   (same service as previous, another tag)    App\EntityHandler\Attribute\Event\PostUpdated    callable        App\EntityHandler\Attribute\EventListener\PostListener   onPostCreatedOrUpdated
  trollbus.handler.3.with_middlewares         App\Handler\Attribute\SomeCommand                callable        App\Handler\Attribute\AttributeBasedHandler              someCommandHandler       Trollbus\MessageBus\Middleware\HandlerWithMiddlewares
  trollbus.handler.4                          App\Handler\Attribute\SomeEvent                  callable        App\Handler\Attribute\AttributeBasedHandler              onSomeEvent1             Trollbus\MessageBus\Handler\CallableHandler
  trollbus.handler.5                          App\Handler\Attribute\SomeEvent                  callable        App\Handler\Attribute\AttributeBasedHandler              onSomeEvent2             Trollbus\MessageBus\Handler\CallableHandler
  trollbus.handler.6.deferred_event_handler   App\EntityHandler\Attribute\Command\CreatePost   entityFactory   App\EntityHandler\Attribute\Post                         createPost               Trollbus\MessageBus\Middleware\HandlerWithMiddlewares
  trollbus.handler.7.deferred_event_handler   App\EntityHandler\Attribute\Command\UpdatePost   entity          App\EntityHandler\Attribute\Post                         updatePost               Trollbus\MessageBus\Middleware\HandlerWithMiddlewares
  trollbus.handler.8.deferred_event_handler   App\EntityHandler\Attribute\Command\UpsertPost   entity          App\EntityHandler\Attribute\Post                         upsertPost               Trollbus\MessageBus\Middleware\HandlerWithMiddlewares
 ------------------------------------------- ------------------------------------------------ --------------- -------------------------------------------------------- ------------------------ -------------------------------------------------------


EOF
)

    # Use "cat" command to remove output colors
    local ACTUAL="$(bin/console debug:container --tag trollbus.handler | cat)"

    # Trim right on expected and actual strings
    EXPECTED=$(echo "$EXPECTED" | sed 's/[[:space:]]*$//')
    ACTUAL=$(echo "$ACTUAL" | sed 's/[[:space:]]*$//')

    test "$EXPECTED" == "$ACTUAL"
}

#####################
# END Declare tests #
#####################

check_no_code_changes && \
run_tests && \
restore_composer_dependencies
