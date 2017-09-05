#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
//gcc fflush-status.c -o fflush-status
//./fflush-status for print status fflush
int
main(int argc, char* argv[])
{
    while(1)
    {
        char buf_ps[1024];
        // docker status
        char ps[]="clear && docker exec -it ctrs_app_1 service ctrsd status";
        // char ps[]="clear && ./ctrsd.sh status";
        char ret[100000] = "";
        FILE *ptr;
        if((ptr=popen(ps, "r"))!=NULL)
        {
             while(fgets(buf_ps, 1024, ptr)!=NULL)
             {
                strcat(ret, buf_ps);
             }
             pclose(ptr);
             ptr = NULL;
        }
        else
        {
         printf("popen %s error/n", ps);
        }
        printf("/r%s", ret);
        fflush(stdout);
        sleep(1);
    }
}