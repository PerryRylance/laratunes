#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>

#define FIFO_PATH "/buffers/now-playing"
#define DESIRED_SIZE (1024 * 1024) // 1 MB

int main() {
    FILE *f = fopen("/proc/sys/fs/pipe-max-size", "r");
    if (!f) {
        perror("fopen pipe-max-size");
        return 1;
    }

    unsigned long max_pipe_size;
    if (fscanf(f, "%lu", &max_pipe_size) != 1) {
        fprintf(stderr, "Failed to read pipe-max-size\n");
        fclose(f);
        return 1;
    }
    fclose(f);

    unsigned long final_size = DESIRED_SIZE < max_pipe_size ? DESIRED_SIZE : max_pipe_size;
    printf("Setting FIFO buffer size to %lu bytes (max allowed: %lu)\n", final_size, max_pipe_size);

    int fd = open(FIFO_PATH, O_RDONLY | O_NONBLOCK);
    if (fd == -1) {
        perror("open FIFO");
        return 1;
    }

    int ret = fcntl(fd, F_SETPIPE_SZ, final_size);
    if (ret == -1) {
        perror("fcntl F_SETPIPE_SZ");
        close(fd);
        return 1;
    }

    printf("FIFO buffer size successfully set to %d bytes\n", ret);

    close(fd);
    return 0;
}
