#include <stdio.h>
#include <fcntl.h>
#include <sys/ioctl.h>
#include <unistd.h>

int main() {
    int fd = open("/buffers/now-playing", O_RDONLY | O_NONBLOCK);
    if (fd == -1) {
        perror("open");
        return 1;
    }

    int bytes_available;
    if (ioctl(fd, FIONREAD, &bytes_available) == -1) {
        perror("ioctl");
        return 1;
    }

    printf("%d\n", bytes_available);

    close(fd);
    return 0;
}
