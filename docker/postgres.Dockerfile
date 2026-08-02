FROM postgres:18.4

RUN apt-get update && \
    apt-get install -y build-essential pgxnclient

RUN pgxn install pgmq
